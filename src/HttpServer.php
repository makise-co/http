<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace MakiseCo\Http;

use Closure;
use MakiseCo\Http\RequestHandler\RequestHandlerFactoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server as SwooleServer;

use function array_merge;
use function swoole_set_process_name;

class HttpServer
{
    public const MODE_MAIN = 'master';
    public const MODE_MANAGER = 'manager';
    public const MODE_WORKER = 'worker';

    protected string $mode = self::MODE_MAIN;

    protected SwooleServer $server;
    protected EventDispatcherInterface $eventDispatcher;
    protected Swoole\SwoolePsrRequestFactoryInterface $requestFactory;
    protected Swoole\SwooleEmitter $emitter;
    protected RequestHandlerInterface $requestHandler;
    protected RequestHandlerFactoryInterface $requestHandlerFactory;

    protected HttpConfig $config;

    protected string $appName;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        Swoole\SwoolePsrRequestFactoryInterface $requestFactory,
        Swoole\SwooleEmitter $emitter,
        RequestHandlerFactoryInterface $requestHandlerFactory,
        HttpConfig $config,
        string $appName
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->requestFactory = $requestFactory;
        $this->emitter = $emitter;
        $this->requestHandlerFactory = $requestHandlerFactory;

        $this->config = $config;
        $this->appName = $appName;
    }

    public function getConfig(): HttpConfig
    {
        return $this->config;
    }

    public function start(string $host, int $port): void
    {
        $this->server = new SwooleServer($host, $port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $this->server->set(
            array_merge(
                [
                    'daemonize' => false,
                    'worker_num' => 1,
                    'send_yield' => true,
                ],
                $this->config->getOptions()
            )
        );

        $this->server->on(
            'Start',
            function (SwooleServer $server) {
                $this->setProcessName('master process');

                $this->eventDispatcher->dispatch(new Events\ServerStarted());
            }
        );

        $this->server->on(
            'ManagerStart',
            function (SwooleServer $server) {
                $this->mode = self::MODE_MANAGER;

                $this->setProcessName('manager process');

                $this->eventDispatcher->dispatch(new Events\ManagerStarted());
            }
        );

        $this->server->on(
            'WorkerStart',
            function (SwooleServer $server, int $workerId) {
                $this->mode = self::MODE_WORKER;

                $this->setProcessName('worker process');

                try {
                    // dispatch before worker started event for early services initialization (before routes resolved)
                    $this->eventDispatcher->dispatch(new Events\BeforeWorkerStarted($workerId, $this));

                    // routes and their dependencies should be resolved before worker will start requests processing
                    $this->requestHandler = $this->requestHandlerFactory->create();

                    // dispatch application level WorkerStarted event
                    $this->eventDispatcher->dispatch(new Events\WorkerStarted($workerId));
                } catch (\Throwable $e) {
                    // stop server if worker cannot be started (to prevent infinite loop)
                    Coroutine::defer(fn() => $server->shutdown());

                    throw $e;
                }
            }
        );

        $this->server->on(
            'WorkerStop',
            function (SwooleServer $server, int $workerId) {
                $this->mode = self::MODE_WORKER;

                // dispatch before worker exit event to stop services
                $this->eventDispatcher->dispatch(new Events\BeforeWorkerExit($workerId, $this));

                $this->eventDispatcher->dispatch(new Events\WorkerStopped($workerId));
            }
        );

        $this->server->on(
            'WorkerExit',
            function (SwooleServer $server, int $workerId) {
                $this->mode = self::MODE_WORKER;

                $this->eventDispatcher->dispatch(new Events\WorkerExit($workerId));
            }
        );

        $this->server->on(
            'Shutdown',
            function (SwooleServer $server) {
                $this->eventDispatcher->dispatch(new Events\ServerShutdown());
            }
        );

        $this->server->on('Request', Closure::fromCallable([$this, 'onRequest']));

        $this->server->start();
    }

    public function stop(): void
    {
        $this->server->shutdown();
    }

    protected function onRequest(Request $request, Response $response): void
    {
        $psrRequest = $this->requestFactory->create($request);

        $psrResponse = $this->requestHandler->handle($psrRequest);

        $this->emitter->emit($response, $psrResponse);
    }

    protected function setProcessName(string $name): void
    {
        if (!empty($this->appName)) {
            swoole_set_process_name("{$this->appName} {$name}");

            return;
        }

        swoole_set_process_name($name);
    }
}
