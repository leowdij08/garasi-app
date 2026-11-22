<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /** Membatasi rute hanya untuk satu role (ADMIN atau PENYEWA). */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user === null || $user->role->value !== $role) {
            abort(403, 'Anda tidak berhak mengakses halaman ini.');
        }

        return $next($request);
    }
}
