<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;

class SocialController extends Controller
{
    public function like(Trip $trip)
    {
        $user = auth()->user();
        $existing = $trip->likes()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            $trip->likes()->create(['user_id' => $user->id]);
            $liked = true;
        }

        return response()->json(['liked' => $liked, 'count' => $trip->likes()->count()]);
    }

    public function comment(Request $request, Trip $trip)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);

        $trip->comments()->create([
            'user_id' => auth()->id(),
            'body'    => $data['body'],
        ]);

        return back()->withFragment('comments');
    }
}
