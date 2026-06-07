<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Backend autocomplete proxy. Keeps keys server-side and lets admin pick the
 * provider: photon (keyless) | geoapify | nominatim. Google uses its own JS widget.
 */
class GeocodeController extends Controller
{
    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $provider = Setting::get('geocode_provider', config('providers.geocode', 'photon'));

        try {
            $out = match ($provider) {
                'geoapify'  => $this->geoapify($q),
                'nominatim' => $this->nominatim($q),
                default     => $this->photon($q),
            };
        } catch (Throwable $e) {
            report($e);
            $out = [];
        }

        return response()->json($out);
    }

    protected function photon(string $q): array
    {
        $features = Http::timeout(8)
            ->get('https://photon.komoot.io/api/', ['q' => $q, 'limit' => 6, 'lang' => 'en'])
            ->json('features', []);

        return collect($features)->map(function ($f) {
            $p = $f['properties'] ?? [];
            $c = $f['geometry']['coordinates'] ?? [null, null];
            $name = collect([$p['name'] ?? null, $p['city'] ?? null, $p['state'] ?? null, $p['country'] ?? null])
                ->filter()->unique()->implode(', ');

            return ['name' => $name, 'lat' => $c[1] ?? null, 'lng' => $c[0] ?? null];
        })->filter(fn ($x) => $x['name'] && $x['lat'])->take(6)->values()->all();
    }

    protected function geoapify(string $q): array
    {
        $key = Setting::get('geoapify_api_key') ?: config('providers.geoapify.key');

        $features = Http::timeout(8)->get('https://api.geoapify.com/v1/geocode/autocomplete', [
            'text' => $q, 'limit' => 6, 'apiKey' => $key,
        ])->json('features', []);

        return collect($features)->map(function ($f) {
            $p = $f['properties'] ?? [];

            return ['name' => $p['formatted'] ?? ($p['city'] ?? ''), 'lat' => $p['lat'] ?? null, 'lng' => $p['lon'] ?? null];
        })->filter(fn ($x) => $x['name'] && $x['lat'])->values()->all();
    }

    protected function nominatim(string $q): array
    {
        $rows = Http::timeout(8)->withHeaders(['User-Agent' => 'Yatri/1.0 (trip planner)'])
            ->get('https://nominatim.openstreetmap.org/search', [
                'q' => $q, 'format' => 'json', 'limit' => 6,
            ])->json();

        return collect($rows ?? [])->map(fn ($x) => [
            'name' => $x['display_name'] ?? '',
            'lat'  => (float) ($x['lat'] ?? 0),
            'lng'  => (float) ($x['lon'] ?? 0),
        ])->filter(fn ($x) => $x['name'])->values()->all();
    }
}
