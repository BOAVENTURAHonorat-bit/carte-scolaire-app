<?php

namespace App\Providers;

use App\Models\School;
use Illuminate\Support\Facades\View;
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
        View::composer(['layouts.navigation', 'layouts.app'], function ($view) {
            $view->with('navSchool', School::first());
        });
    }
}
