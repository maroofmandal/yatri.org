<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function index(Request $request)
    {
        $trips = Trip::query()
            ->when($request->q, fn ($query, $q) => $query->where(
                fn ($w) => $w->where('title', 'like', "%$q%")->orWhere('origin', 'like', "%$q%")
            ))
            ->when($request->status, fn ($query, $s) => $query->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.trips.index', compact('trips'));
    }

    public function show(Trip $trip)
    {
        return view('admin.trips.show', compact('trip'));
    }

    public function toggle(Trip $trip)
    {
        $trip->update(['is_public' => ! $trip->is_public]);

        return back()->with('ok', 'Visibility updated.');
    }

    public function destroy(Trip $trip)
    {
        $trip->delete();

        return back()->with('ok', 'Trip deleted.');
    }
}
