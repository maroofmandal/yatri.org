<?php

namespace App\Services\Gemini;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin REST client for the Gemini API (generativelanguage.googleapis.com).
 *
 * Admin DB settings override env config. Supports two mutually-exclusive modes:
 *   - grounded text  (Google Search + Google Maps tools)   -> opts['grounding'=>true]
 *   - structured JSON (responseSchema, no tools)            -> opts['schema'=>[...]]
 * Gemini does not allow grounding tools and a JSON responseSchema in one call,
 * so the planner runs them as two passes.
 */
class GeminiClient
{
    public function apiKey(): ?string
    {
        return Setting::get('gemini_api_key') ?: config('gemini.key');
    }

    public function model(): string
    {
        return Setting::get('gemini_model') ?: config('gemini.model', 'gemini-flash-latest');
    }

    public function enabled(): bool
    {
        return ! empty($this->apiKey());
    }

    protected function groundingTools(): array
    {
        $tools = [];
        if (Setting::get('gemini_grounding_search', config('gemini.grounding_search'))) {
            $tools[] = ['google_search' => (object) []];
        }
        if (Setting::get('gemini_grounding_maps', config('gemini.grounding_maps'))) {
            $tools[] = ['google_maps' => (object) []];
        }
        return $tools;
    }

    /**
     * @return array{text:string, grounding:array, usage:array, model:string}
     */
    public function generate(string $system, string $user, array $opts = []): array
    {
        $key = $this->apiKey();
        if (! $key) {
            throw new RuntimeException('Gemini API key not configured.');
        }

        $model    = $opts['model'] ?? $this->model();
        $schema   = $opts['schema'] ?? null;
        $grounded = $opts['grounding'] ?? false;
        $temp     = $opts['temperature'] ?? 0.7;

        $body = [
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents'          => [['role' => 'user', 'parts' => [['text' => $user]]]],
            'generationConfig'  => array_filter([
                'temperature'      => $temp,
                'responseMimeType' => $schema ? 'application/json' : null,
                'responseSchema'   => $schema,
            ], fn ($v) => $v !== null),
        ];

        // Build tool fallbacks: full grounding -> search only -> none.
        $toolSets = [];
        if ($grounded && ! $schema) {
            $tools = $this->groundingTools();
            if ($tools) {
                $toolSets[] = $tools;
                $toolSets[] = [['google_search' => (object) []]];
            }
        }
        $toolSets[] = null;

        // Model fallback chain. Free-tier quota is PER-DAY, PER-MODEL
        // (GenerateRequestsPerDayPerProjectPerModel-FreeTier), so when one model is
        // exhausted (429) we advance to a different model with its own daily bucket.
        // Order: chosen model first, then high-headroom 'lite' models, then alternates.
        $models = array_values(array_unique(array_filter([
            $model,
            'gemini-2.5-flash',
            'gemini-2.5-flash-lite',
            'gemini-2.0-flash-lite',
            'gemini-flash-latest',
            'gemini-2.0-flash',
        ])));

        $base = rtrim(config('gemini.base'), '/');
        $lastError = null;

        foreach ($models as $candidate) {
            $url = "{$base}/models/{$candidate}:generateContent";

            foreach ($toolSets as $tools) {
                $payload = $body;
                if ($tools) {
                    $payload['tools'] = $tools;
                }

                $status = null;

                // Up to 3 attempts with exponential backoff on transient overload (503/500).
                for ($attempt = 0; $attempt < 3; $attempt++) {
                    $resp = Http::timeout(180)
                        ->withHeaders(['x-goog-api-key' => $key])
                        ->acceptJson()
                        ->post($url, $payload);

                    if ($resp->successful()) {
                        return $this->parse($resp->json(), $candidate);
                    }

                    $status = $resp->status();
                    $lastError = $status . ': ' . $resp->body();

                    if (in_array($status, [500, 503], true)) {
                        usleep((int) (800_000 * (2 ** $attempt))); // 0.8s, 1.6s, 3.2s
                        continue;
                    }

                    break; // 429/400/403/404 → no point retrying the SAME model/call
                }

                // 429 → this model's daily bucket is exhausted; advance to next model
                // (separate per-model quota). No sleep — daily quota won't clear in-request.
                if ($status === 429) {
                    break; // next model
                }

                // 400 → unsupported tool: drop to next, smaller tool set (same model).
                if ($status === 400) {
                    continue;
                }

                // 503/500 (still failing after retries) or 403/404 → try next model.
                break;
            }
        }

        throw new RuntimeException('Gemini request failed — ' . $lastError);
    }

    protected function parse(array $json, string $model): array
    {
        $candidate = $json['candidates'][0] ?? [];
        $parts     = $candidate['content']['parts'] ?? [];

        $text = collect($parts)->pluck('text')->filter()->implode("\n");

        $grounding = [];
        foreach (($candidate['groundingMetadata']['groundingChunks'] ?? []) as $chunk) {
            if (isset($chunk['web'])) {
                $grounding[] = [
                    'title' => $chunk['web']['title'] ?? '',
                    'uri'   => $chunk['web']['uri'] ?? '',
                    'type'  => 'web',
                ];
            } elseif (isset($chunk['maps'])) {
                $grounding[] = [
                    'title' => $chunk['maps']['title'] ?? ($chunk['maps']['text'] ?? 'Place'),
                    'uri'   => $chunk['maps']['uri'] ?? '',
                    'type'  => 'maps',
                ];
            }
        }

        return [
            'text'      => $text,
            'grounding' => $grounding,
            'usage'     => [
                'prompt' => $json['usageMetadata']['promptTokenCount'] ?? 0,
                'output' => $json['usageMetadata']['candidatesTokenCount'] ?? 0,
            ],
            'model' => $model,
        ];
    }
}
