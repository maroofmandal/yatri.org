<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FxController extends Controller
{
    /**
     * GET /api/fx/{currency}
     *
     * Returns { rate: float, source: string } for converting USD → target currency.
     * Tries the live exchange-rate API first, then a CDN fallback, then admin-set rates.
     */
    public function rate(string $currency)
    {
        $currency = strtoupper(trim($currency));

        if ($currency === 'USD') {
            return response()->json(['rate' => 1, 'source' => 'base']);
        }

        // Try live rates (cached 6 h). Both CDN mirrors share the same data shape.
        $rates = Cache::remember('fx:usd', now()->addHours(6), function () {
            foreach ($this->endpoints() as $url) {
                try {
                    $res = Http::timeout(5)->get($url);
                    if ($res->ok()) {
                        $body = $res->json();
                        if (isset($body['usd']) && is_array($body['usd'])) {
                            return ['rates' => $body['usd'], 'source' => 'live'];
                        }
                    }
                } catch (\Throwable) {
                    // try next
                }
            }

            return null; // signal: live failed
        });

        // Live hit — extract the requested currency
        if ($rates && isset($rates['rates'][strtolower($currency)])) {
            return response()->json([
                'rate'   => (float) $rates['rates'][strtolower($currency)],
                'source' => 'live',
            ]);
        }

        // Fallback: admin-configured rates
        $fallback = Setting::get('fx_rates', []);
        $key = strtolower($currency);

        if (is_array($fallback) && isset($fallback[$key])) {
            return response()->json([
                'rate'   => (float) $fallback[$key],
                'source' => 'admin',
            ]);
        }

        // Nothing available
        return response()->json(['rate' => null, 'source' => 'none'], 404);
    }

    /**
     * GET /api/fx
     *
     * Returns all available USD-based rates (for admin / bulk use).
     */
    public function all()
    {
        $rates = Cache::remember('fx:usd', now()->addHours(6), function () {
            foreach ($this->endpoints() as $url) {
                try {
                    $res = Http::timeout(5)->get($url);
                    if ($res->ok()) {
                        $body = $res->json();
                        if (isset($body['usd']) && is_array($body['usd'])) {
                            return ['rates' => $body['usd'], 'source' => 'live'];
                        }
                    }
                } catch (\Throwable) {
                    // try next
                }
            }

            return null;
        });

        if ($rates) {
            return response()->json($rates);
        }

        $fallback = Setting::get('fx_rates', []);

        return response()->json([
            'rates'  => is_array($fallback) ? $fallback : [],
            'source' => 'admin',
        ]);
    }

    /** CDN endpoints — the primary jsdelivr mirror + Cloudflare fallback. */
    protected function endpoints(): array
    {
        return [
            'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.min.json',
            'https://latest.currency-api.pages.dev/v1/currencies/usd.min.json',
        ];
    }
}
