<?php

namespace App\Services\Gemini;

use App\Models\Setting;
use App\Services\ApiKeyManager;
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
 *
 * API key resolution:
 *   1. Round-robin from api_keys table (service='gemini', if any active keys exist)
 *   2. Single key from settings table (legacy)
 *   3. Single key from env config (legacy)
 * On 429 the key is marked exhausted and the next key/model is tried.
 */
class GeminiClient
{
    protected ?ApiKeyManager $keyManager = null;

    protected function getApiKeyManager(): ApiKeyManager
    {
        if (! $this->keyManager) {
            $this->keyManager = app(ApiKeyManager::class);
        }
        return $this->keyManager;
    }

    public function apiKey(): ?string
    {
        // Prefer round-robin from api_keys table
        if ($this->getApiKeyManager()->available(ApiKeyManager::SERVICE_GEMINI)) {
            return $this->getApiKeyManager()->nextKey(ApiKeyManager::SERVICE_GEMINI);
        }

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
     * Try to get an API key, with optional tracking.
     * @return array{key:string, tracked:bool}|null
     */
    protected function resolveKey(): ?array
    {
        $km = $this->getApiKeyManager();
        if ($km->available(ApiKeyManager::SERVICE_GEMINI)) {
            $tracked = $km->nextTrackedKey(ApiKeyManager::SERVICE_GEMINI);
            if ($tracked) {
                return ['key' => $tracked['key'], 'tracked_id' => $tracked['id']];
            }
        }
        $legacy = Setting::get('gemini_api_key') ?: config('gemini.key');
        if ($legacy) {
            return ['key' => $legacy, 'tracked_id' => 0];
        }
        return null;
    }

    /**
     * @return array{text:string, grounding:array, usage:array, model:string}
     */
    public function generate(string $system, string $user, array $opts = []): array
    {
        $model    = $opts['model'] ?? $this->model();
        $schema   = $opts['schema'] ?? null;
        $grounded = $opts['grounding'] ?? false;
        $temp     = $opts['temperature'] ?? 0.7;

        $thinkingBudget = array_key_exists('thinking_budget', $opts)
            ? $opts['thinking_budget']
            : ($schema ? 0 : null);

        $body = [
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents'          => [['role' => 'user', 'parts' => [['text' => $user]]]],
            'generationConfig'  => array_filter([
                'temperature'      => $temp,
                'responseMimeType' => $schema ? 'application/json' : null,
                'responseSchema'   => $schema,
                'thinkingConfig'   => $thinkingBudget === null ? null : ['thinkingBudget' => $thinkingBudget],
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

        // Model fallback chain
        $models = array_values(array_unique(array_filter([
            $model,
            'gemini-2.5-flash',
            'gemini-2.5-flash-lite',
            'gemini-2.0-flash-lite',
            'gemini-flash-latest',
            'gemini-2.0-flash',
        ])));

        $base      = rtrim(config('gemini.base'), '/');
        $lastError = null;
        $km        = $this->getApiKeyManager();
        $usedKeys  = [];

        for ($keyAttempt = 0; $keyAttempt < 5; $keyAttempt++) {
            $keyData = $this->resolveKey();
            if (! $keyData) {
                throw new RuntimeException('Gemini API key not configured.');
            }

            $key      = $keyData['key'];
            $keyId    = $keyData['tracked_id'];

            // Avoid looping the same key if multiple keys in pool
            if (in_array($keyId, $usedKeys, true)) {
                break;
            }
            $usedKeys[] = $keyId;

            foreach ($models as $candidate) {
                $url = "{$base}/models/{$candidate}:generateContent";

                foreach ($toolSets as $tools) {
                    $payload = $body;
                    if ($tools) {
                        $payload['tools'] = $tools;
                    }

                    $status = null;

                    for ($attempt = 0; $attempt < 3; $attempt++) {
                        $resp = Http::timeout(180)
                            ->withHeaders(['x-goog-api-key' => $key])
                            ->acceptJson()
                            ->post($url, $payload);

                        if ($resp->successful()) {
                            return $this->parse($resp->json(), $candidate);
                        }

                        $status    = $resp->status();
                        $lastError = $status . ': ' . $resp->body();

                        if (in_array($status, [500, 503], true)) {
                            usleep((int) (800_000 * (2 ** $attempt)));
                            continue;
                        }

                        break;
                    }

                    if ($status === 429) {
                        if ($keyId > 0) {
                            $km->markExhaustedById($keyId, $lastError);
                        }
                        break; // try next model (loop will try a new key)
                    }

                    if ($status === 400) {
                        continue; // drop to smaller tool set
                    }

                    break; // 403/404/500/503 → next model
                }
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
            'model'  => $model,
            'finish' => $candidate['finishReason'] ?? null,
        ];
    }
}
