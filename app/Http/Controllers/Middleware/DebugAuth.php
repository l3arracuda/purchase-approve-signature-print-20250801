<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DebugAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            Log::info('Auth Debug', [
                'user_id' => $user->id,
                'username' => $user->username,
                'auth_id' => Auth::id(),
                'auth_id_type' => gettype(Auth::id()),
                'session_id' => $request->session()->getId(),
            ]);
        }
        
        return $next($request);
    }
}