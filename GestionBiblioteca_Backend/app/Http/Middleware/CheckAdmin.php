<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Enums\Rol;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->Rol_ID !== Rol::ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Se requieren privilegios de Administrador.'
            ], 403);
        }

        return $next($request);
    }
}