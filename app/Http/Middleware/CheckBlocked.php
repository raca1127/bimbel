<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckBlocked
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if ($user && property_exists($user, 'is_blocked') && $user->is_blocked) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Akun Anda diblokir. Hubungi admin.');
        }
        return $next($request);
    }
}
