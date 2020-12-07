# Makise-Co HTTP Server
Makise-Co Swoole HTTP Server implementation

## Installation
* Register service provider - `MakiseCo\Http\HttpServiceProvider`
* Register commands (look at [src/Commands](src/Commands))
* Minimal required configuration [config](config)
* Routes declaration [routes](routes)

## Example configuration
```php
// config/http.php

use function MakiseCo\Env\env;

return [
    'host' => env('HTTP_HOST', '127.0.0.1'),
    'port' => (int)env('HTTP_PORT', 10228),

    'options' => [
        'worker_num' => (int)env('HTTP_WORKER_NUM', fn() => \swoole_cpu_num()),
        'reactor_num' => (int)env('HTTP_REACTOR_NUM', fn() => \swoole_cpu_num()),
    ],

    'routes' => [
        __DIR__ . '/../routes/api.php',
    ],

    // global middleware list
    'middleware' => [

    ],

    // list of services that should be initialized before a worker starts processing requests
    // and which should be stopped before a worker exits
    // empty list means - all services should be initialized
    // [null] means - services shouldn't be initialized
    'services' => [

    ],
];
```

## Available commands
* `routes:dump` shows information about application routes
* `http:start` starts HTTP server

## Testing the application
This component provides a set of useful tools to test application without running HTTP server.

* [MakesHttpRequests](src/Testing/MakesHttpRequests.php) trait (Laravel-like trait for your TestCases to invoke app routes)
