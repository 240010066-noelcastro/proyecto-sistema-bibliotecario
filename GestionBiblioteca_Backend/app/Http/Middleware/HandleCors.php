<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleCors
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
public function handle($request, Closure $next)
{
    $origin = $request->header('Origin');
    
    // Lista de dominios autorizados (desarrollo local + variables de entorno)
    $allowedOrigins = array_filter(
        explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:8100,http://localhost:5173,http://localhost:3000'))
    );

    if ($request->isMethod('OPTIONS')) {
    $response = response('', 200);
    } else {
    $response = $next($request);
    }

    if (in_array($origin, $allowedOrigins)) {
        $response->header('Access-Control-Allow-Origin', $origin);
    }

    return $response
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
}
}
