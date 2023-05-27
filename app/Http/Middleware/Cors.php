<?php

/**
 * @author: pujiermanto@gmail.com
 * @param CorsMiddleware
 * */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\{ApiKeys};

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        $api_key = $request->header('dku_api_key');
        $check_api_onDb = ApiKeys::whereToken($api_key)->first();


        if($check_api_onDb !== NULL){
            return $next($request)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Authorization');
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Access blocked!'
            ], 404);
        }
    }
}
