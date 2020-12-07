<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace MakiseCo\Http\Tests;

use MakiseCo\Application;
use MakiseCo\Bootstrapper;
use MakiseCo\Http\Events\ServerStarted;
use MakiseCo\Http\Events\WorkerStarted;
use MakiseCo\Http\HttpServer;
use PHPUnit\Framework\TestCase;
use Swoole\Atomic;
use Swoole\Timer;
use Symfony\Component\EventDispatcher\EventDispatcher;

class HttpServerTest extends TestCase
{
    public function testItWorks(): void
    {
        $_ENV['HTTP_WORKER_NUM'] = $_ENV['HTTP_REACTOR_NUM'] = 1;
        $_SERVER['HTTP_WORKER_NUM'] = $_SERVER['HTTP_REACTOR_NUM'] = 1;

        $application = new Application(
            dirname(__DIR__),
            dirname(__DIR__) . '/config'
        );

        $container = $application->getContainer();

        $pm = new ProcessManager();
        $pm->parentFunc = static function () use ($pm) {
            $response = file_get_contents("http://127.0.0.1:{$pm->getFreePort()}/");
            echo $response;

            file_get_contents("http://127.0.0.1:{$pm->getFreePort()}/shutdown");
        };
        $pm->childFunc = static function () use ($pm, $container) {
            $server = $container->get(HttpServer::class);

            $container->get(EventDispatcher::class)->addListener(ServerStarted::class, function () use ($pm) {
                $pm->wakeup();
            });

            $server->start('127.0.0.1', $pm->getFreePort());
        };

        $pm->setWaitTimeout(3);
        $pm->childFirst();

        ob_start();
        $pm->run();
        $output = ob_get_clean();

        self::assertSame('{"message":"Hello, Okabe!"}', $output);
    }

    public function testBootstrapperCalled(): void
    {
        $_ENV['HTTP_WORKER_NUM'] = $_ENV['HTTP_REACTOR_NUM'] = 1;
        $_SERVER['HTTP_WORKER_NUM'] = $_SERVER['HTTP_REACTOR_NUM'] = 1;

        $application = new Application(
            dirname(__DIR__),
            dirname(__DIR__) . '/config'
        );

        $container = $application->getContainer();

        $initCalled = new Atomic(0);
        $stopCalled = new Atomic(0);

        $pm = new ProcessManager();
        $pm->parentFunc = static function () use ($pm) {
            $response = file_get_contents("http://127.0.0.1:{$pm->getFreePort()}/");
            echo $response;

            file_get_contents("http://127.0.0.1:{$pm->getFreePort()}/shutdown");
        };
        $pm->childFunc = static function () use ($pm, $container, $initCalled, $stopCalled) {
            $server = $container->get(HttpServer::class);

            $container->get(Bootstrapper::class)->addService(
                'test',
                static function () use ($initCalled) {
                    $initCalled->set(1);
                },
                static function () use ($stopCalled) {
                    $stopCalled->set(1);
                },
            );

            $container->get(EventDispatcher::class)->addListener(ServerStarted::class, function () use ($pm) {
                $pm->wakeup();
            });

            $server->start('127.0.0.1', $pm->getFreePort());
        };

        $pm->setWaitTimeout(3);
        $pm->childFirst();

        ob_start();
        $pm->run();
        $output = ob_get_clean();

        self::assertSame('{"message":"Hello, Okabe!"}', $output);
        self::assertSame(1, $initCalled->get());
        self::assertSame(1, $stopCalled->get());
    }

    public function testServerShutdownOnWorkerStartError(): void
    {
        $_ENV['HTTP_WORKER_NUM'] = $_ENV['HTTP_REACTOR_NUM'] = 1;
        $_SERVER['HTTP_WORKER_NUM'] = $_SERVER['HTTP_REACTOR_NUM'] = 1;

        $application = new Application(
            dirname(__DIR__),
            dirname(__DIR__) . '/config'
        );

        $container = $application->getContainer();

        $pm = new ProcessManager();
        $pm->parentFunc = static function () use ($pm) {
            try {
                $response = file_get_contents("http://127.0.0.1:{$pm->getFreePort()}/");
                echo $response;

                file_get_contents("http://127.0.0.1:{$pm->getFreePort()}/shutdown");
            } catch (\Throwable $e) {
                echo "error";
            }
        };
        $pm->childFunc = static function () use ($pm, $container) {
            $server = $container->get(HttpServer::class);

            $container->get(EventDispatcher::class)->addListener(WorkerStarted::class, function () use ($pm) {
                Timer::after(1000, function () use ($pm) {
                    $pm->kill(true);
                });

                throw new \RuntimeException('Testing server shutdown');
            });

            $container->get(EventDispatcher::class)->addListener(ServerStarted::class, function () use ($pm) {
                $pm->wakeup();
            });

            $server->start('127.0.0.1', $pm->getFreePort());
        };

        $pm->setWaitTimeout(3);
        $pm->childFirst();

        ob_start();
        $pm->run();
        $output = ob_get_clean();

        self::assertSame('error', $output);
    }
}
