<?php
namespace DevTools\LaravelIdempotent\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class IdempotentService
{
    public static function generateKey($request): string
    {
        $headerKey = $request->header('Idempotency-Key');
        if ($headerKey) {
            return 'idempotent_' . $headerKey;
        }

        $data = $request->json()->all() ?: [];
        ksort($data);

        $payloadHash = hash('sha256', json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return 'idempotent_' . hash('sha256', $request->path() . $payloadHash);
    }

    public static function isDuplicate($key)
    {
        return Cache::has($key);
    }

    public static function storeKey(string $key, $response): void
    {
        Cache::put($key, [
            'status' => $response->status(),
            'content' => $response->getContent(),
            'headers' => $response->headers->all()
        ], config('idempotent.ttl', 3600));
    }

    public static function duplicateResponse(string $key)
    {
        if(config('idempotent.enable_logging')) {
            Log::info('duplicate found', ['key' => $key]);
        }
        
        $mode = config('idempotent.mode', 'replay');
        $cached = Cache::get($key);

        if ($mode == 'block' || !$cached) {
            $status = config('idempotent.duplicate_response', 409);
            return response()->json(['message' => 'Duplicate request detected'], $status);
        }

        // Replay cached response
        return response($cached['content'], $cached['status'] ?? 200)
        ->withHeaders($cached['headers'] ?? [])
        ->header('Content-Type', 'application/json');
    }
}