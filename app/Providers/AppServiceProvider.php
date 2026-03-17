<?php

namespace App\Providers;

use App\Models\Application;
use App\Observers\ApplicationObserver;
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
     * 
     * Register model observers for activity logging.
     * This provides trigger-like functionality using Laravel events.
     */
    public function boot(): void
    {
        // Register Application observer for activity logging
        // This acts as an alternative to database triggers
        Application::observe(ApplicationObserver::class);
    }
}
