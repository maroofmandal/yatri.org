<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Review;
use App\Models\Trip;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}
    public function store(Request $request)
    {
        $data = $request->validate([
            'reviewable_type' => 'required|in:destination,hotel,flight,trip',
            'reviewable_id' => 'required|integer',
            'rating' => 'required|integer|between:1,5',
            'title' => 'nullable|string|max:255',
            'body' => 'required|string|max:2000',
        ]);

        $reviewableType = match($data['reviewable_type']) {
            'destination' => Destination::class,
            'hotel' => \App\Models\Hotel::class ?? null,
            'flight' => \App\Models\Flight::class ?? null,
            'trip' => Trip::class,
        };

        if (!$reviewableType || !$reviewableType::find($data['reviewable_id'])) {
            return back()->withErrors(['reviewable_id' => 'Invalid review target.']);
        }

        $review = auth()->user()->reviews()->create([
            'reviewable_type' => $reviewableType,
            'reviewable_id' => $data['reviewable_id'],
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'],
        ]);

        $this->notificationService->sendReviewNotification(auth()->user(), $review);

        return back()->with('ok', 'Review submitted!');
    }

    public function destroy(Review $review)
    {
        if ($review->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $review->delete();

        return back()->with('ok', 'Review deleted!');
    }
}