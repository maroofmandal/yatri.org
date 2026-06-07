<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->q, fn ($query, $q) => $query->where(
                fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")
            ))
            ->withCount('trips')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function updateRole(Request $request, User $user)
    {
        $data = $request->validate(['role' => ['required', 'in:user,admin']]);

        if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
            return back()->with('error', 'You cannot remove your own admin role.');
        }

        $user->update($data);

        return back()->with('ok', "Role updated for {$user->name}.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete yourself.');
        }

        $user->delete();

        return back()->with('ok', 'User deleted.');
    }
}
