<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SupabaseAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('user_id')) {
            return redirect('/login')->with('error', 'Giriş yapmanız gerekiyor.');
        }
        
        return $next($request);
    }
}