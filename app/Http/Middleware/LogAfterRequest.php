<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class LogAfterRequest
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        Log::info($request->ip);
        Log::info($request->url);
        Log::info($request->headers->all());
        Log::info($request->all());
    }
}
