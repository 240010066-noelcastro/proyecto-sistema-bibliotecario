<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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
    // Limita a 60 peticiones por minuto POR USUARIO (o por IP si no ha iniciado sesión)
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->Usuario_ID ?: $request->ip());
    });

    // Limita intentos de login por combinación de Correo + IP
    RateLimiter::for('login', function (Request $request) {
        return Limit::perMinute(5)->by($request->input('correo') . $request->ip());
    });
    }
}