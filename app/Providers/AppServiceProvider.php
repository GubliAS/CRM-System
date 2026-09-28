<?php

namespace App\Providers;

use App\Models\SupportCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Vite::prefetch(concurrency: 3);

        Password::defaults(fn () => Password::min(8)->mixedCase()->numbers());

        Auth::guard('web')->setRememberDuration((int) config('auth.remember_minutes'));

        Route::bind('case', function (string $value): SupportCase {
            return SupportCase::query()->whereKey($value)->firstOrFail();
        });
    }
}
