<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('components.pagination');

        // Data notifikasi untuk dropdown (navbar publik & dashboard).
        View::composer(['partials.navbar', 'layouts.dashboard'], function ($view) {
            $user = auth()->user();
            $view->with('unreadCount', $user ? $user->unreadNotifications()->count() : 0)
                ->with('recentNotifications', $user ? $user->notifications()->latest()->take(5)->get() : collect())
                ->with('pendingCount', $user?->isAdmin() ? \App\Models\Karya::where('status', 'menunggu')->count() : 0)
                ->with('newReportCount', $user?->isAdmin() ? \App\Models\Report::where('status', 'baru')->count() : 0);
        });

        // Middleware "throttle:unggah" pada diagram Sequence: maks. 6 unggahan per menit per pengguna.
        RateLimiter::for('unggah', function (Request $request) {
            return Limit::perMinute(config('kreatifgallery.upload.throttle_per_minute'))
                ->by($request->user()?->id ?: $request->ip());
        });
    }
}
