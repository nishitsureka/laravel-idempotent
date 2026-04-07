<?php
namespace Nishit\LaravelIdempotent\Middleware;

use Closure;
use Illuminate\Http\Request;
use Nishit\LaravelIdempotent\Services\IdempotentService;

class Idempotent
{
    public function handle(Request $request, Closure $next)
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key') ?? IdempotentService::generateKey($request);

        if (IdempotentService::isDuplicate($key)) {
            return IdempotentService::duplicateResponse($key);
        }

        $response = $next($request);
        IdempotentService::storeKey($key, $response);

        return $response;
    }
}