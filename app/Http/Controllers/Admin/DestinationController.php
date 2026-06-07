<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    public function index(Request $request)
    {
        $destinations = Destination::query()
            ->when($request->q, fn ($query, $q) => $query->where('name', 'like', "%$q%"))
            ->orderByDesc('popularity')
            ->paginate(20)
            ->withQueryString();

        return view('admin.destinations.index', compact('destinations'));
    }

    public function store(Request $request)
    {
        Destination::create($this->validated($request));

        return back()->with('ok', 'Destination added.');
    }

    public function update(Request $request, Destination $destination)
    {
        $destination->update($this->validated($request));

        return back()->with('ok', 'Destination updated.');
    }

    public function destroy(Destination $destination)
    {
        $destination->delete();

        return back()->with('ok', 'Destination deleted.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name'           => ['required', 'string', 'max:120'],
            'country'        => ['nullable', 'string', 'max:80'],
            'lat'            => ['nullable', 'numeric'],
            'lng'            => ['nullable', 'numeric'],
            'summary'        => ['nullable', 'string', 'max:500'],
            'image'          => ['nullable', 'string', 'max:300'],
            'avg_daily_cost' => ['nullable', 'integer', 'min:0'],
            'popularity'     => ['nullable', 'integer', 'min:0'],
            'is_active'      => ['nullable', 'boolean'],
        ]);
    }
}
