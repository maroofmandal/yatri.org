<?php

namespace App\Services\Llm;

use App\Models\Setting;
use App\Services\Gemini\GeminiClient;
use App\Services\Search\SearchProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Unified LLM facade. Routes to the admin-selected provider:
 *   gemini     → native REST (self-grounds via Google Search/Maps)
 *   groq       → OpenAI-compatible (Llama). Grounds via SearchProvider.
 *   openrouter → OpenAI-compatible (free models). Grounds via SearchProvider.
 *
 * Same signature as GeminiClient so TripPlanner is provider-agnostic.
 */
class LlmClient
{
    public function __construct(
        protected GeminiClient $gemini,
        protected SearchProvider $search,
    ) {}

    public function provider(): string
    {
        return Setting::get('llm_provider', config('providers.llm', 'gemini'));
    }

    public function enabled(): bool
    {
        return match ($this->provider()) {
            'groq'       => ! empty($this->groqKey()),
            'openrouter' => ! empty($this->openrouterKey()),
            default      => $this->gemini->enabled(),
        };
    }

    public function model(): string
    {
        return match ($this->provider()) {
            'groq'       => Setting::get('groq_model', config('providers.groq.model')),
            'openrouter' => Setting::get('openrouter_model', config('providers.openrouter.model')),
            default      => $this->gemini->model(),
        };
    }

    /**
     * @return array{text:string, grounding:array, usage:array, model:string}
     */
    public function generate(string $system, string $user, array $opts = []): array
    {
        if ($this->provider() === 'gemini') {
            return $this->gemini->generate($system, $user, $opts);
        }

        return $this->openAiCompatible($system, $user, $opts);
    }

    protected function groqKey(): ?string
    {
        return Setting::get('groq_api_key') ?: config('providers.groq.key');
    }

    protected function openrouterKey(): ?string
    {
        return Setting::get('openrouter_api_key') ?: config('providers.openrouter.key');
    }

    protected function openAiCompatible(string $system, string $user, array $opts): array
    {
        $provider = $this->provider();

        if ($provider === 'groq') {
            $base  = config('providers.groq.base');
            $key   = $this->groqKey();
            $model = Setting::get('groq_model', config('providers.groq.model'));
        } else {
            $base  = config('providers.openrouter.base');
            $key   = $this->openrouterKey();
            $model = Setting::get('openrouter_model', config('providers.openrouter.model'));
        }

        if (! $key) {
            throw new RuntimeException(ucfirst($provider) . ' API key not configured.');
        }

        // Inject free web-search grounding for research passes (not JSON passes).
        $grounding = [];
        if (($opts['grounding'] ?? false) && empty($opts['schema']) && $this->search->enabled()) {
            $res = $this->search->search(strip_tags($user));
            if ($res['snippets']) {
                $user = "Live web search results (use these for current facts):\n{$res['snippets']}\n\n" . $user;
                $grounding = $res['citations'];
            }
        }

        $body = [
            'model'       => $model,
            'messages'    => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => $opts['temperature'] ?? 0.7,
        ];

        if (! empty($opts['schema'])) {
            $body['response_format'] = ['type' => 'json_object'];
            $body['messages'][0]['content'] .= "\nRespond ONLY with a single valid JSON object — no markdown, no prose.";
        }

        $resp = Http::timeout(180)->withToken($key)->acceptJson()
            ->post(rtrim($base, '/') . '/chat/completions', $body);

        if (! $resp->successful()) {
            throw new RuntimeException(ucfirst($provider) . ' request failed — ' . $resp->status() . ': ' . $resp->body());
        }

        $json = $resp->json();

        return [
            'text'      => $json['choices'][0]['message']['content'] ?? '',
            'grounding' => $grounding,
            'usage'     => [
                'prompt' => $json['usage']['prompt_tokens'] ?? 0,
                'output' => $json['usage']['completion_tokens'] ?? 0,
            ],
            'model' => $model,
        ];
    }
}
