<?php

namespace App\Services;

use App\Models\ApiKey;

/**
 * Manages round-robin API key rotation across multiple keys per service.
 *
 * When a key returns 429 (rate-limit / quota exhausted), it is marked inactive
 * and the next key in the pool is tried transparently.
 */
class ApiKeyManager
{
    const SERVICE_GEMINI = 'gemini';
    const SERVICE_NANO_BANANA = 'nano_banana';

    /**
     * Get the next available key for the given service (round-robin).
     * Returns the raw key string or null if none available.
     */
    public function nextKey(string $service): ?string
    {
        $key = ApiKey::nextFor($service);

        if (! $key) {
            return null;
        }

        $key->update(['last_used_at' => now()]);

        return $key->key;
    }

    /**
     * Get the next key wrapped as [model_id => key_string] for tracking.
     */
    public function nextTrackedKey(string $service): ?array
    {
        $key = ApiKey::nextFor($service);

        if (! $key) {
            return null;
        }

        $key->update(['last_used_at' => now()]);

        return ['id' => $key->id, 'key' => $key->key];
    }

    /**
     * Mark a specific key as exhausted (429 received).
     */
    public function markExhausted(string $service, string $key, string $error = ''): void
    {
        ApiKey::where('service', $service)
            ->where('key', $key)
            ->update([
                'is_active'  => false,
                'last_error' => $error,
            ]);
    }

    /**
     * Mark a key by its ID as exhausted.
     */
    public function markExhaustedById(int $id, string $error = ''): void
    {
        ApiKey::where('id', $id)->update([
            'is_active'  => false,
            'last_error' => $error,
        ]);
    }

    /**
     * Reactivate all keys for a service (admin action).
     */
    public function refreshAll(string $service): int
    {
        return ApiKey::where('service', $service)
            ->where('is_active', false)
            ->update([
                'is_active'  => true,
                'last_error' => null,
            ]);
    }

    /**
     * Count active keys for a service.
     */
    public function activeCount(string $service): int
    {
        return ApiKey::active($service)->count();
    }

    /**
     * Check if any keys are available for a service.
     */
    public function available(string $service): bool
    {
        return $this->activeCount($service) > 0;
    }
}
