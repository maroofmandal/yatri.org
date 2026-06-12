<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Services\ApiKeyManager;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function __construct(
        protected ApiKeyManager $keyManager
    ) {}

    /**
     * Store a new API key.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'service' => ['required', 'in:gemini,nano_banana'],
            'key'     => ['required', 'string', 'max:500'],
            'label'   => ['nullable', 'string', 'max:100'],
        ]);

        ApiKey::create([
            'service' => $data['service'],
            'key'     => $data['key'],
            'label'   => $data['label'] ?? null,
        ]);

        return back()->with('ok', 'API key added.');
    }

    /**
     * Delete an API key.
     */
    public function destroy(ApiKey $apiKey)
    {
        $apiKey->delete();
        return back()->with('ok', 'API key removed.');
    }

    /**
     * Reactivate all exhausted keys for a service.
     */
    public function refresh(Request $request)
    {
        $service = $request->validate(['service' => 'required|in:gemini,nano_banana'])['service'];
        $count   = $this->keyManager->refreshAll($service);
        return back()->with('ok', "Reactivated {$count} key(s) for {$service}.");
    }
}
