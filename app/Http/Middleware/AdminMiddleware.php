<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user(); 

        if ($user && $user->profile && $user->profile->role === 'admin') {
            return $next($request);
        }

        return response()->json(['error' => 'Unauthorized (admin)'], 403);
    }
}
