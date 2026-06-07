<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlanRequest;
use App\Models\Destination;
use App\Models\Trip;
use App\Services\Planner\TripPlanner;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PlannerController extends Controller
{
    public function create()
    {
        $destinations = Destination::active()->orderByDesc('popularity')->limit(12)->get();
        $recent = Trip::where('is_public', true)->where('status', 'ready')->latest()->limit(6)->get();

        return view('planner.create', compact('destinations', 'recent'));
    }

    public function store(StorePlanRequest $request)
    {
        $data = $request->validated();

        $dests = collect($data['destinations'])->map(fn ($d) => [
            'name'   => $d['name'],
            'nights' => (int) ($d['nights'] ?? 2),
            'lat'    => $d['lat'] ?? null,
            'lng'    => $d['lng'] ?? null,
        ])->all();

        $trip = Trip::create([
            'user_id'      => $request->user()?->id,
            'title'        => $this->defaultTitle($data['origin'], $dests),
            'origin'       => $data['origin'],
            'destinations' => $dests,
            'start_date'   => $data['start_date'] ?? null,
            'end_date'     => $data['end_date'] ?? null,
            'days'         => $this->computeDays($data, $dests),
            'travelers'    => $data['travelers'],
            'budget_total' => $data['budget_total'],
            'currency'     => strtoupper($data['currency']),
            'style'        => $data['style'],
            'interests'    => $data['interests'] ?? [],
            'status'       => 'draft',
        ]);

        return redirect()->route('trip.show', $trip);
    }

    public function show(Trip $trip)
    {
        abort_unless($trip->is_public || $this->canManage($trip), 404);

        if ($trip->isReady()) {
            $trip->increment('views');

            return view('planner.show', compact('trip'));
        }

        return view('planner.generating', compact('trip'));
    }

    /** Called by the loader on the generating page (synchronous generation). */
    public function generate(Trip $trip, TripPlanner $planner)
    {
        if (! $trip->isReady() || request()->boolean('force')) {
            $planner->generate($trip);
        }

        $trip->refresh();

        return response()->json([
            'status'   => $trip->status,
            'error'    => $trip->error,
            'redirect' => route('trip.show', $trip),
        ]);
    }

    public function regenerate(Trip $trip)
    {
        abort_unless($this->canManage($trip), 403);

        $trip->update([
            'status'    => 'draft',
            'plan'      => null,
            'error'     => null,
        ]);

        return redirect()->route('trip.show', $trip);
    }

    public function chat(Request $request, Trip $trip, TripPlanner $planner)
    {
        $data = $request->validate(['question' => ['required', 'string', 'max:500']]);

        return response()->json($planner->chat($trip, $data['question']));
    }

    // ── helpers ──

    protected function computeDays(array $data, array $dests): int
    {
        if (! empty($data['start_date']) && ! empty($data['end_date'])) {
            return Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;
        }

        $nights = array_sum(array_map(fn ($d) => (int) ($d['nights'] ?? 2), $dests));

        return max(1, $nights);
    }

    protected function defaultTitle(string $origin, array $dests): string
    {
        $names = collect($dests)->pluck('name');
        $head = $names->take(2)->implode(' & ');

        if ($names->count() > 2) {
            $head .= ' +' . ($names->count() - 2);
        }

        return $head ?: 'Trip from ' . $origin;
    }

    protected function canManage(Trip $trip): bool
    {
        if ($trip->user_id === null) {
            return true; // guest-created — the link holder manages it
        }

        $user = request()->user();

        return $user && ($user->isAdmin() || $trip->user_id === $user->id);
    }
}
