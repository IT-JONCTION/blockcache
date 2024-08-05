<?php

namespace Itjonction\Blockcache;

use Closure;
use Illuminate\Http\Request;

class ConditionalCacheInvalidationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
