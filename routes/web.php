<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FxController;
use App\Http\Controllers\GeocodeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlannerController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\StaticPageController;
use App\Models\Trip;
use Illuminate\Support\Facades\Route;

// ── Sitemap ───────────────────────────────────────────────────────
Route::get('/sitemap.xml', [SitemapController::class, 'index']);

// ── Social home / discovery ─────────────────────────────────────
Route::get('/', [FeedController::class, 'index'])->name('home');
Route::get('/explore/trips', [FeedController::class, 'trips'])->name('trips.explore');
Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');
Route::get('/geo/suggest', [GeocodeController::class, 'suggest'])->name('geo.suggest');
Route::get('/api/fx', [FxController::class, 'all'])->name('fx.all');
Route::get('/api/fx/{currency}', [FxController::class, 'rate'])->name('fx.rate');
Route::get('/u/{user}', [ProfileController::class, 'show'])->name('profile');

// ── Static pages ─────────────────────────────────────────────────
Route::get('/about', [StaticPageController::class, 'about'])->name('about');
Route::get('/privacy', [StaticPageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [StaticPageController::class, 'terms'])->name('terms');
Route::get('/contact', [StaticPageController::class, 'contact'])->name('contact');
Route::post('/contact', [StaticPageController::class, 'contactStore'])->name('contact.store');

// ── Rankings ─────────────────────────────────────────────────────
Route::get('/rankings', [RankingController::class, 'index'])->name('rankings');

// ── Posts ────────────────────────────────────────────────────────
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
Route::get('/posts/viewer/{id}', [PostController::class, 'viewer'])->name('posts.viewer');
Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

// ── Planner ─────────────────────────────────────────────────────
Route::get('/plan', [PlannerController::class, 'create'])->name('planner');
Route::post('/plan', [PlannerController::class, 'store'])->name('plan.store');
Route::get('/t/{trip}', [PlannerController::class, 'show'])->name('trip.show');
Route::post('/t/{trip}/generate', [PlannerController::class, 'generate'])->name('trip.generate');
Route::post('/t/{trip}/chat', [PlannerController::class, 'chat'])->name('trip.chat');
Route::post('/t/{trip}/regenerate', [PlannerController::class, 'regenerate'])->name('trip.regenerate');
Route::post('/t/{trip}/update', [PlannerController::class, 'update'])->name('trip.update');

// ── Social actions (auth) ───────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/u/{user}/follow', [ProfileController::class, 'follow'])->name('profile.follow');
    Route::delete('/u/{user}/follow', [ProfileController::class, 'unfollow'])->name('profile.unfollow');
    
    Route::post('/t/{trip}/like', [SocialController::class, 'like'])->name('trip.like');
    Route::post('/t/{trip}/comment', [SocialController::class, 'comment'])->name('trip.comment');
    
    Route::post('/posts/{postId}/like', [SocialController::class, 'likePost'])->name('post.like');
    Route::post('/posts/{postId}/comment', [SocialController::class, 'commentPost'])->name('post.comment');
    Route::post('/comments/{comment}/reply', [SocialController::class, 'reply'])->name('comment.reply');
    
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');

    // ── Settings ─────────────────────────────────────────────────
    Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/avatar', [SettingsController::class, 'updateAvatar'])->name('settings.avatar');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/theme', [SettingsController::class, 'updateTheme'])->name('settings.theme');
});

// ── Share tracking (no auth required) ────────────────────────────
Route::post('/api/trip/{id}/share', [SocialController::class, 'shareTrip'])->name('trip.share');
Route::post('/posts/{postId}/share', [SocialController::class, 'sharePost'])->name('post.share');

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
    $posts = \App\Models\Post::where('user_id', Auth::id())->latest()->get();

    return view('dashboard', compact('trips', 'posts'));
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

    // API key management for round-robin
    Route::post('/api-keys', [Admin\ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::delete('/api-keys/{apiKey}', [Admin\ApiKeyController::class, 'destroy'])->name('api-keys.destroy');
    Route::post('/api-keys/refresh', [Admin\ApiKeyController::class, 'refresh'])->name('api-keys.refresh');
});