<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\Comment;
use App\Models\Consultation;
use App\Models\Deal;
use App\Models\Email;
use App\Models\FollowupAuthPerson;
use App\Models\FollowupBusiness;
use App\Models\FollowupDetail;
use App\Models\SeoDetail;
use App\Models\User;
use App\Observers\FollowupDetailObserver;
use App\Observers\GlobalSearchObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('ai-chat', function (Request $request) {
            return Limit::perMinute((int) config('ai.security.rate_limit_per_minute', 10))
                ->by((string) ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('ai-chat-status', function (Request $request) {
            return Limit::perMinute((int) config('ai.security.status_rate_limit_per_minute', 30))
                ->by((string) ($request->user()?->id ?? $request->ip()));
        });

        FollowupDetail::observe(FollowupDetailObserver::class);

        $globalSearchObserver = GlobalSearchObserver::class;
        FollowupBusiness::observe($globalSearchObserver);
        FollowupAuthPerson::observe($globalSearchObserver);
        Deal::observe($globalSearchObserver);
        Appointment::observe($globalSearchObserver);
        User::observe($globalSearchObserver);
        Email::observe($globalSearchObserver);
        Consultation::observe($globalSearchObserver);
        SeoDetail::observe($globalSearchObserver);
        Comment::observe($globalSearchObserver);
    }
}
