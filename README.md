# Laravel Idempotent

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)

Laravel Idempotent is a **lightweight middleware package** to make POST, PUT, and PATCH requests idempotent.  
It prevents duplicate processing (e.g., double orders, repeated payments, or accidental form resubmissions).

---

## Features

- Automatic generation of **idempotency keys** based on request path + payload.
- Optional manual idempotency via `Idempotency-Key` header.
- Configurable **modes**:
  - `replay` – return cached response for duplicate requests
  - `block` – return HTTP status 409 or configured status
- Cache-based storage (supports `cache` or `redis`).
- Route-specific middleware; no need to apply globally.
- Logging support for debugging duplicate requests.

---

## Installation

Install via Composer:

```bash
composer require nishit/laravel-idempotent
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Nishit\LaravelIdempotent\LaravelIdempotentServiceProvider" --tag=config
```

---

## Configuration

Edit the published `config/idempotent.php`:

```php
return [
    'ttl' => 3600,                   // Cache duration in seconds
    'storage' => 'cache',            // 'cache' or 'redis'
    'duplicate_response' => 409,     // HTTP status for duplicates in block mode
    'enable_logging' => env('APP_DEBUG', false),
    'mode' => 'replay',              // 'replay' or 'block'
];
```

| Key                  | Description                                          |
|----------------------|------------------------------------------------------|
| `ttl`                | How long the request response will be cached.        |
| `storage`            | Choose your cache driver (`cache` or `redis`).       |
| `duplicate_response` | HTTP status returned for duplicates in block mode.   |
| `enable_logging`     | Log duplicate requests for debugging.                |
| `mode`               | `replay` returns cached response, `block` returns an error. |

---

## Usage

Apply middleware to selected routes:

```php
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Single route
Route::post('/submit', function (\Illuminate\Http\Request $request) {
    return response()->json(['message' => 'Processed', 'data' => $request->all()]);
})->middleware('idempotent');

Route::post('/order', [OrderController::class, 'create'])->middleware('idempotent');

// Or group routes
Route::middleware(['idempotent'])->group(function () {
    Route::post('/order', [OrderController::class, 'create']);
});
```

> Only routes using the middleware will have idempotency applied. Other routes remain unaffected.

---

## How It Works

1. Middleware generates a unique key per request using:
   - Request path
   - Request payload (POST / PUT / PATCH)
   - Optional `Idempotency-Key` header

2. If the key **exists** in cache:
   - **Replay mode** → returns cached response
   - **Block mode** → returns HTTP 409 or configured status

3. If the key **does not exist**, the request is processed and the response is cached for future duplicates.

---

## Testing

Use `curl` to test idempotency:

```bash
# First request
curl -X POST http://127.0.0.1:8000/api/submit \
  -H "Content-Type: application/json" \
  -d '{"name":"John","email":"john@example.com"}'

# Repeat request with same payload or manual Idempotency-Key header
curl -X POST http://127.0.0.1:8000/api/submit \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: 123456" \
  -d '{"name":"John","email":"john@example.com"}'
```

- In **replay** mode, the second request returns the cached response.
- In **block** mode, the second request returns HTTP `409` (or the configured status).

---

## Logging

Enable logging in `config/idempotent.php`:

```php
'enable_logging' => true,
```

Duplicate requests will be logged in `storage/logs/laravel.log`:

```
[2026-04-07 09:24:00] local.INFO: duplicate found {"key":"idempotent_<hash>"}
```

---

## License

This package is open-sourced software licensed under the [MIT License](https://opensource.org/licenses/MIT).
