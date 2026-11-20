<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penggunaan: ->middleware('role:kreator') atau 'role:admin' atau 'role:kreator,admin'.
 * Dijalankan setelah 'auth'. Role tidak cocok => 403 (sesuai diagram Sequence 2a).
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
