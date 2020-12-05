<?php

declare(strict_types=1);

namespace MakiseCo\Http;

class HttpConfig
{
    private array $options;

    private array $middleware;
    private array $services;

    public function __construct(array $options, array $middleware, array $services)
    {
        $this->options = $options;
        $this->middleware = $middleware;
        $this->services = $services;
    }

    /**
     * Get swoole server options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get (global) middleware list that should be applied before each request
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Get services list that should be initialized before a worker starts processing requests
     * and which should be stopped before a worker exits
     *
     * @return array
     */
    public function getServices(): array
    {
        return $this->services;
    }

    public static function fromArray(array $arr): self
    {
        return new self(
            $arr['options'] ?? $arr['swoole'] ?? [],
            $arr['middleware'] ?? [],
            $arr['services'] ?? [],
        );
    }
}
