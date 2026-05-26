<?php

namespace App\Providers;

use App\Models\FollowupDetail;
use App\Observers\FollowupDetailObserver;
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
        FollowupDetail::observe(FollowupDetailObserver::class);
    }
}
