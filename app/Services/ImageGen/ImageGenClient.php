<?php

namespace App\Services\ImageGen;

use App\Models\Setting;
use App\Services\ApiKeyManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Gemini Nano Banana image generation client.
 *
 * Uses the gemini-3.1-flash-image (Nano Banana 2) model by default
 * with round-robin API key rotation and automatic key exhaustion on 429.
 */
class ImageGenClient
{
    protected ApiKeyManager $keyManager;

    protected string $service = ApiKeyManager::SERVICE_NANO_BANANA;

    public function __construct()
    {
        $this->keyManager = app(ApiKeyManager::class);
    }

    public function model(): string
    {
        return Setting::get('nano_banana_model') ?: config('gemini.nano_banana_model', 'gemini-3.1-flash-image');
    }

    public function enabled(): bool
    {
        // NanoBanana uses the same Google Generative Language API as Gemini,
        // so fall back to Gemini keys if no dedicated NanoBanana keys exist.
        return $this->keyManager->available($this->service)
            || $this->keyManager->available(ApiKeyManager::SERVICE_GEMINI)
            || ! empty(config('gemini.key'));
    }

    /**
     * Resolve an API key with fallback chain:
     * 1. NanoBanana keys from api_keys table (round-robin)
     * 2. Gemini keys from api_keys table (round-robin)
     * 3. Legacy Nanobanana key from settings/env
     * 4. Gemini env key (GEMINI_API_KEY)
     */
    protected function resolveKey(): array
    {
        $keyData = $this->keyManager->nextTrackedKey($this->service);
        if ($keyData) {
            return $keyData;
        }

        // NanoBanana uses the same Google API as Gemini — fall back to Gemini keys
        $keyData = $this->keyManager->nextTrackedKey(ApiKeyManager::SERVICE_GEMINI);
        if ($keyData) {
            return $keyData;
        }

        // Fallback to the legacy single-key setting so existing configs still work
        $legacy = Setting::get('nano_banana_api_key') ?: config('gemini.nano_banana_key');
        if ($legacy) {
            return ['id' => 0, 'key' => $legacy];
        }

        // Final fallback: Gemini env key (same Google API endpoint)
        $envKey = config('gemini.key');
        if ($envKey) {
            return ['id' => 0, 'key' => $envKey];
        }

        throw new RuntimeException('Nano Banana API key not configured.');
    }

    /**
     * Generate an image from a text prompt.
     *
     * @return array{data:string, mime:string, model:string, key_id:int}
     */
    public function generateImage(string $prompt, array $opts = []): array
    {
        $aspectRatio = $opts['aspect_ratio'] ?? '16:9';
        $imageSize   = $opts['image_size'] ?? '1K';

        $keyData = $this->resolveKey();
        $key     = $keyData['key'];
        $keyId   = $keyData['id'];
        $model   = $opts['model'] ?? $this->model();

        // Model fallback chain: try configured model, then known good models
        $models = array_values(array_unique(array_filter([
            $model,
            'gemini-3.1-flash-image',
            'gemini-2.5-flash-image',
        ])));

        $base     = rtrim(config('gemini.base', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $lastError = null;

        foreach ($models as $candidate) {
            $url = "{$base}/models/{$candidate}:generateContent";

            $payload = [
                'contents' => [
                    [
                        'role'  => 'user',
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'responseModalities' => ['TEXT', 'IMAGE'],
                    'temperature'        => $opts['temperature'] ?? 0.4,
                    'imageConfig'        => [
                        'aspectRatio' => $aspectRatio,
                        'imageSize'   => $imageSize,
                    ],
                ],
            ];

            $status   = null;
            $attempts = 0;
            $retry429 = 0;

            for ($attempt = 0; $attempt < 3; $attempt++) {
                $resp = Http::timeout(120)
                    ->withHeaders(['x-goog-api-key' => $key])
                    ->acceptJson()
                    ->post($url, $payload);

                if ($resp->successful()) {
                    $result = $this->parse($resp->json(), $candidate);

                    return [
                        'data'   => $result['data'],
                        'mime'   => $result['mime'],
                        'model'  => $candidate,
                        'key_id' => $keyId,
                    ];
                }

                $status    = $resp->status();
                $lastError = $status . ': ' . $resp->body();

                // 429 = rate limited. Wait for suggested delay, then retry.
                if ($status === 429) {
                    $retry429++;
                    $body = $resp->json();
                    $delay = 0;
                    if (isset($body['error']['details'])) {
                        foreach ($body['error']['details'] as $detail) {
                            if (isset($detail['retryDelay'])) {
                                $delay = max($delay, (int) preg_replace('/[^0-9]/', '', $detail['retryDelay']));
                            }
                        }
                    }
                    $delay = min(max($delay ?: 2, 1), 10);
                    Log::info("ImageGen 429 for {$candidate} (attempt {$attempt}), waiting {$delay}s...");
                    sleep($delay);
                    continue;
                }

                if (in_array($status, [500, 503], true)) {
                    usleep((int) (800_000 * (2 ** $attempt)));
                    continue;
                }

                break;
            }

            // All 3 attempts returned 429 — key truly exhausted
            if ($status === 429 && $retry429 >= 3) {
                if ($keyId > 0) {
                    $this->keyManager->markExhaustedById($keyId, $lastError);
                    Log::warning("Nano Banana key #{$keyId} exhausted (429 after {$retry429} retries).");
                }
                continue; // Try next model
            }

            // Non-retryable error with this model: try next
            if (in_array($status, [400, 403, 404], true)) {
                continue;
            }

            break;
        }

        throw new RuntimeException('Image generation failed — ' . ($lastError ?? 'unknown error'));
    }

    /**
     * Save generated image data to storage and return the public path.
     */
    public function saveImage(string $subdir, array $imageData): string
    {
        $filename = $subdir . '/' . md5(uniqid()) . '.webp';
        $data     = base64_decode($imageData['data']);
        $image    = @imagecreatefromstring($data);

        if ($image !== false) {
            ob_start();
            imagewebp($image, null, 80);
            $webpData = ob_get_clean();
            imagedestroy($image);
            Storage::disk('public')->put($filename, $webpData);
        } else {
            Storage::disk('public')->put($filename, $data);
        }

        return $filename;
    }

    protected function parse(array $json, string $model): array
    {
        $candidate = $json['candidates'][0] ?? [];
        $parts     = $candidate['content']['parts'] ?? [];

        foreach ($parts as $part) {
            if (isset($part['inlineData']['data'])) {
                return [
                    'data' => $part['inlineData']['data'],
                    'mime' => $part['inlineData']['mimeType'] ?? 'image/png',
                ];
            }
        }

        // Check finish reason for useful error info
        $finish = $candidate['finishReason'] ?? 'UNKNOWN';
        $safetyRatings = $candidate['safetyRatings'] ?? [];

        throw new RuntimeException(
            "No image data in response. Finish reason: {$finish}. " .
            json_encode($safetyRatings)
        );
    }
}
