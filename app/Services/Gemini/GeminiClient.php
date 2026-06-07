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

        $url = rtrim(config('gemini.base'), '/') . "/models/{$model}:generateContent";
        $lastError = null;

        foreach ($toolSets as $tools) {
            $payload = $body;
            if ($tools) {
                $payload['tools'] = $tools;
            }

            $resp = Http::timeout(180)
                ->withHeaders(['x-goog-api-key' => $key])
                ->acceptJson()
                ->post($url, $payload);

            if ($resp->successful()) {
                return $this->parse($resp->json(), $model);
            }

            $lastError = $resp->status() . ': ' . $resp->body();

            // Retry with fewer tools only on a 400 (likely an unsupported tool).
            if ($resp->status() !== 400) {
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
