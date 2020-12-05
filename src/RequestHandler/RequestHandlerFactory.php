<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace MakiseCo\Http\RequestHandler;

use MakiseCo\Http\HttpConfig;
use MakiseCo\Http\Router\RouteCollectorInterface;
use MakiseCo\Middleware\ErrorHandlingMiddleware;
use MakiseCo\Middleware\MiddlewarePipeFactory;
use MakiseCo\Middleware\MiddlewareResolver;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandlerFactory implements RequestHandlerFactoryInterface
{
    private ContainerInterface $container;
    private RouteCollectorInterface $routeCollector;
    private HttpConfig $httpConfig;

    public function __construct(
        ContainerInterface $container,
        RouteCollectorInterface $routeCollector,
        HttpConfig $httpConfig
    ) {
        $this->container = $container;
        $this->routeCollector = $routeCollector;
        $this->httpConfig = $httpConfig;
    }

    public function create(): RequestHandlerInterface
    {
        $pipeline = [ErrorHandlingMiddleware::class];

        foreach ($this->httpConfig->getMiddleware() as $middleware) {
            $pipeline[] = $middleware;
        }

        $pipeline[] = $this->routeCollector->getRouter();

        return (new MiddlewarePipeFactory(new MiddlewareResolver($this->container)))
            ->create($pipeline);
    }
}
