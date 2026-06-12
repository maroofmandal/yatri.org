<?php

namespace App\Http\Controllers;

use App\Helpers\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function show()
    {
        $user = auth()->user();

        return view('settings', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'             => ['required', 'string', 'max:120'],
            'bio'              => ['nullable', 'string', 'max:500'],
            'current_city'     => ['nullable', 'string', 'max:120'],
            'default_currency' => ['required', 'string', 'size:3'],
            'age'              => ['nullable', 'integer', 'min:1', 'max:120'],
            'travel_preferences' => ['nullable', 'string', 'max:1000'],
        ]);

        $user->fill($data);
        $user->save();

        return back()->with('ok', 'Profile updated.');
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = auth()->user();

        if ($user->avatar_url && str_starts_with($user->avatar_url, Storage::disk('public')->url('avatars'))) {
            $oldUrl = $user->avatar_url;
            $prefix = Storage::disk('public')->url('avatars') . '/';
            $oldPath = 'avatars/' . substr($oldUrl, strlen($prefix));
            Storage::disk('public')->delete($oldPath);
            $legacy = preg_replace('/\.webp$/', '.png', $oldPath);
            if ($legacy !== $oldPath) Storage::disk('public')->delete($legacy);
            $legacyJpg = preg_replace('/\.webp$/', '.jpg', $oldPath);
            if ($legacyJpg !== $oldPath) Storage::disk('public')->delete($legacyJpg);
        }

        $file = $request->file('avatar');
        $path = ImageOptimizer::optimizeAvatar($file);
        $url = Storage::disk('public')->url($path);

        $user->avatar_url = $url;
        $user->save();

        return back()->with('ok', 'Profile photo updated.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = auth()->user();
        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('ok', 'Password changed.');
    }

    public function updateTheme(Request $request)
    {
        $request->validate([
            'theme' => ['required', 'in:light,dark,auto'],
        ]);

        $user = auth()->user();
        $user->theme = $request->theme;
        $user->save();

        return response()->json(['ok' => true, 'theme' => $user->theme]);
    }
}
