<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FxController;
use App\Http\Controllers\GeocodeController;
use App\Http\Controllers\PlannerController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SocialController;
use App\Models\Trip;
use Illuminate\Support\Facades\Route;

// ── Social home / discovery ─────────────────────────────────────
Route::get('/', [FeedController::class, 'index'])->name('home');
Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');
Route::get('/geo/suggest', [GeocodeController::class, 'suggest'])->name('geo.suggest');
Route::get('/api/fx', [FxController::class, 'all'])->name('fx.all');
Route::get('/api/fx/{currency}', [FxController::class, 'rate'])->name('fx.rate');
Route::get('/u/{user}', [ProfileController::class, 'show'])->name('profile');

// ── Planner ─────────────────────────────────────────────────────
Route::get('/plan', [PlannerController::class, 'create'])->name('planner');
Route::post('/plan', [PlannerController::class, 'store'])->name('plan.store');
Route::get('/t/{trip}', [PlannerController::class, 'show'])->name('trip.show');
Route::post('/t/{trip}/generate', [PlannerController::class, 'generate'])->name('trip.generate');
Route::post('/t/{trip}/chat', [PlannerController::class, 'chat'])->name('trip.chat');
Route::post('/t/{trip}/regenerate', [PlannerController::class, 'regenerate'])->name('trip.regenerate');

// ── Social actions (auth) ───────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/u/{user}/follow', [ProfileController::class, 'follow'])->name('profile.follow');
    Route::delete('/u/{user}/follow', [ProfileController::class, 'unfollow'])->name('profile.unfollow');
    Route::post('/t/{trip}/like', [SocialController::class, 'like'])->name('trip.like');
    Route::post('/t/{trip}/comment', [SocialController::class, 'comment'])->name('trip.comment');
});

// ── Auth ────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ── User dashboard ──────────────────────────────────────────────
Route::get('/dashboard', function () {
    $trips = Trip::where('user_id', Auth::id())->latest()->get();

    return view('dashboard', compact('trips'));
})->middleware('auth')->name('dashboard');

// ── Admin panel ─────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/trips', [Admin\TripController::class, 'index'])->name('trips.index');
    Route::get('/trips/{trip:id}', [Admin\TripController::class, 'show'])->name('trips.show');
    Route::patch('/trips/{trip:id}/toggle', [Admin\TripController::class, 'toggle'])->name('trips.toggle');
    Route::delete('/trips/{trip:id}', [Admin\TripController::class, 'destroy'])->name('trips.destroy');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/role', [Admin\UserController::class, 'updateRole'])->name('users.role');
    Route::delete('/users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');

    Route::get('/destinations', [Admin\DestinationController::class, 'index'])->name('destinations.index');
    Route::post('/destinations', [Admin\DestinationController::class, 'store'])->name('destinations.store');
    Route::put('/destinations/{destination}', [Admin\DestinationController::class, 'update'])->name('destinations.update');
    Route::delete('/destinations/{destination}', [Admin\DestinationController::class, 'destroy'])->name('destinations.destroy');

    Route::get('/gemini', [Admin\GeminiLogController::class, 'index'])->name('gemini.index');
});
