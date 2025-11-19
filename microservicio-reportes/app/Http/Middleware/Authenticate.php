<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // No redirigir a login, devolver error 401
        if ($request->expectsJson()) {
            return null;
        }

        return null; // No redirigir nunca
    }
}
