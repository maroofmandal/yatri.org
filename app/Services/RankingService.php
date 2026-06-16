<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class RankingService
{
    public function getTopTravelers(string $period = 'all', int $limit = 100)
    {
        $query = User::query()
            ->withRankingCounts()
            ->selectRaw('
                users.total_days_traveled * 2 +
                users.total_kilometers * 0.1 +
                users.total_likes_received * 0.5 +
                (select count(*) from follows where follows.following_id = users.id) * 1.5
            as ranking_score');

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

        return $query->orderBy('ranking_score', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getUserRanking(User $user, string $period = 'all'): int
    {
        $topTravelers = $this->getTopTravelers($period, 1000);
        
        $rank = $topTravelers->pluck('id')->search($user->id);
        
        return $rank !== false ? $rank + 1 : null;
    }

    public function updateUserStats(User $user): void
    {
        $stats = [
            'total_days_traveled' => $user->trips()
                ->where('status', 'ready')
                ->sum('days'),
            'total_kilometers' => $this->calculateTotalKilometers($user),
            'total_likes_received' => $this->calculateTotalLikes($user),
        ];

        $user->update($stats);
    }

    protected function calculateTotalKilometers(User $user): float
    {
        $trips = $user->trips()->where('status', 'ready')->get();
        
        $totalKm = 0;
        
        foreach ($trips as $trip) {
            if ($trip->origin_lat && $trip->origin_lng) {
                $prevLat = $trip->origin_lat;
                $prevLng = $trip->origin_lng;
                
                foreach ($trip->destinations as $dest) {
                    if (isset($dest['lat'], $dest['lng'])) {
                        $totalKm += $this->haversine($prevLat, $prevLng, $dest['lat'], $dest['lng']);
                        $prevLat = $dest['lat'];
                        $prevLng = $dest['lng'];
                    }
                }
            }
        }
        
        return $totalKm;
    }

    protected function calculateTotalLikes(User $user): int
    {
        $tripLikes = $user->trips()->sum('likes_count');
        $postLikes = DB::table('likes')
            ->where('likeable_type', 'App\\Models\\Post')
            ->whereIn('likeable_id', $user->posts()->pluck('id'))
            ->count();
        $reviewLikes = DB::table('likes')
            ->where('likeable_type', 'App\\Models\\Review')
            ->whereIn('likeable_id', $user->reviews()->pluck('id'))
            ->count();
            
        return $tripLikes + $postLikes + $reviewLikes;
    }

    protected function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km
        
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}