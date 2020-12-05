<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

/** @var \MakiseCo\Http\Router\RouteCollectorInterface $routes */

use Laminas\Diactoros\Response\JsonResponse;

$routes->get('/', function (): JsonResponse {
    return new JsonResponse(['message' => 'Hello, Okabe!']);
});

$routes->get('/shutdown', function (\MakiseCo\Http\HttpServer $server): JsonResponse {
    $server->stop();

    return new JsonResponse(['message' => 'OK']);
});
