<?php

namespace App\Services\Search;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Free web-search grounding for non-Gemini LLMs (Gemini grounds itself).
 * Providers: none | tavily | brave.
 */
class SearchProvider
{
    public function provider(): string
    {
        return Setting::get('search_provider', config('providers.search', 'none'));
    }

    protected function key(): ?string
    {
        return match ($this->provider()) {
            'tavily' => Setting::get('tavily_api_key') ?: config('providers.tavily.key'),
            'brave'  => Setting::get('brave_api_key') ?: config('providers.brave.key'),
            default  => null,
        };
    }

    public function enabled(): bool
    {
        return $this->provider() !== 'none' && ! empty($this->key());
    }

    /**
     * @return array{snippets:string, citations:array}
     */
    public function search(string $query): array
    {
        if (! $this->enabled()) {
            return ['snippets' => '', 'citations' => []];
        }

        try {
            return $this->provider() === 'tavily'
                ? $this->tavily($query)
                : $this->brave($query);
        } catch (Throwable $e) {
            report($e);

            return ['snippets' => '', 'citations' => []];
        }
    }

    protected function tavily(string $q): array
    {
        $res = Http::timeout(30)->post('https://api.tavily.com/search', [
            'api_key'      => $this->key(),
            'query'        => $q,
            'max_results'  => 5,
            'search_depth' => 'basic',
        ])->json('results', []);

        $snippets = '';
        $citations = [];
        foreach ($res as $r) {
            $snippets .= '- ' . ($r['title'] ?? '') . ': ' . ($r['content'] ?? '') . "\n";
            $citations[] = ['title' => $r['title'] ?? '', 'uri' => $r['url'] ?? '', 'type' => 'web'];
        }

        return ['snippets' => $snippets, 'citations' => $citations];
    }

    protected function brave(string $q): array
    {
        $res = Http::timeout(30)
            ->withHeaders(['X-Subscription-Token' => $this->key(), 'Accept' => 'application/json'])
            ->get('https://api.search.brave.com/res/v1/web/search', ['q' => $q, 'count' => 5])
            ->json('web.results', []);

        $snippets = '';
        $citations = [];
        foreach ($res as $r) {
            $snippets .= '- ' . ($r['title'] ?? '') . ': ' . ($r['description'] ?? '') . "\n";
            $citations[] = ['title' => $r['title'] ?? '', 'uri' => $r['url'] ?? '', 'type' => 'web'];
        }

        return ['snippets' => $snippets, 'citations' => $citations];
    }
}
