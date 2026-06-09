<?php

namespace App\Http\Controllers;

use App\Services\RankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RankingController extends Controller
{
    public function __construct(
        protected RankingService $rankingService
    ) {}

    public function index(Request $request)
    {
        $period = $request->get('period', 'all');
        $category = $request->get('category', 'overall');
        
        $travelers = $this->getTravelersByCategory($category, $period);
        
        $stats = [
            'total_countries' => \App\Models\Destination::active()->count(),
            'total_trips' => \App\Models\Trip::where('status', 'ready')->count(),
            'total_users' => \App\Models\User::count(),
        ];

        return view('rankings', compact('travelers', 'period', 'category', 'stats'));
    }

    protected function getTravelersByCategory(string $category, string $period)
    {
        $query = \App\Models\User::query()
            ->select('users.*')
            ->selectRaw('(select count(*) from trips where trips.user_id = users.id and status = ?) as trips_count', ['ready'])
            ->selectRaw('(select count(*) from follows where follows.following_id = users.id) as followers_count')
            ->selectRaw('(select count(*) from posts where posts.user_id = users.id) as posts_count')
            ->selectRaw('(select count(*) from reviews where reviews.user_id = users.id) as reviews_count');

        if ($period !== 'all') {
            $dateFilter = match($period) {
                'year' => now()->subYear(),
                'month' => now()->subMonth(),
                'week' => now()->subWeek(),
                default => null,
            };

            if ($dateFilter) {
                $query->whereHas('trips', function ($q) use ($dateFilter) {
                    $q->where('created_at', '>=', $dateFilter);
                });
            }
        }

        switch ($category) {
            case 'destinations':
                return $query->orderBy('total_days_traveled', 'desc')->limit(50)->get();
                
            case 'kilometers':
                return $query->orderBy('total_kilometers', 'desc')->limit(50)->get();
                
            case 'days':
                return $query->orderBy('total_days_traveled', 'desc')->limit(50)->get();
                
            case 'likes':
                return $query->orderBy('total_likes_received', 'desc')->limit(50)->get();
                
            case 'followers':
                return $query->orderBy('followers_count', 'desc')->limit(50)->get();
                
            default: // overall
                return $this->rankingService->getTopTravelers($period, 50);
        }
    }
}