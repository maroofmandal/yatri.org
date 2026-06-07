<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\GeminiLog;
use App\Models\Trip;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users'        => User::count(),
            'trips'        => Trip::count(),
            'trips_ready'  => Trip::where('status', 'ready')->count(),
            'trips_failed' => Trip::where('status', 'failed')->count(),
            'gemini_calls' => GeminiLog::count(),
            'tokens'       => (int) (GeminiLog::sum('prompt_tokens') + GeminiLog::sum('output_tokens')),
            'destinations' => Destination::count(),
        ];

        $recentTrips = Trip::latest()->limit(8)->get();
        $recentLogs  = GeminiLog::with('trip')->latest()->limit(10)->get();

        return view('admin.dashboard', compact('stats', 'recentTrips', 'recentLogs'));
    }
}
