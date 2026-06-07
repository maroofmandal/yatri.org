<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeminiLog;
use Illuminate\Http\Request;

class GeminiLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = GeminiLog::query()
            ->with('trip')
            ->when($request->kind, fn ($query, $k) => $query->where('kind', $k))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $totals = [
            'calls'  => GeminiLog::count(),
            'tokens' => (int) (GeminiLog::sum('prompt_tokens') + GeminiLog::sum('output_tokens')),
            'errors' => GeminiLog::where('status', 'error')->count(),
        ];

        return view('admin.gemini.index', compact('logs', 'totals'));
    }
}
