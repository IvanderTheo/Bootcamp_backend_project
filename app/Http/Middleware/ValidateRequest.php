<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ValidateRequest
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if(!$request->user()||$request->user()->role!== 'admin') {
            return response()->json([
                'message'=>'Akses ditolak, Hanya admin',
            ],403);
        }
        return $next($request);
    }
}
