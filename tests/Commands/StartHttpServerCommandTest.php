<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace MakiseCo\Http\Tests\Commands;

use MakiseCo\Application;
use MakiseCo\Http\HttpServer;
use PHPUnit\Framework\TestCase;

class StartHttpServerCommandTest extends TestCase
{
    public function testItWorks(): void
    {
        $application = new Application(
            dirname(__DIR__) . '/..',
            dirname(__DIR__) . '/../config'
        );

        $mock = $this->createMock(HttpServer::class);
        $mock
            ->expects(self::once())
            ->method('start')
            ->with('127.0.0.1', 3222);

        $application->getContainer()->set(HttpServer::class, fn() => $mock);

        $args = ['', 'http:start', '--host=127.0.0.1', '--port=3222'];
        $application->run($args);
    }
}
