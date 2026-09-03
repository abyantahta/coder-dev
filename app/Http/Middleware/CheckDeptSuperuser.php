<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckDeptSuperuser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user) abort(403);

        // IT Superadmin bisa akses semua
        if ($user->isSuperAdmin()) return $next($request);

        if (! $user->isDeptSuperuser()) {
            abort(403, 'Unauthorized. Hanya Dept Superuser yang bisa mengakses halaman ini.');
        }

        return $next($request);
    }
}
