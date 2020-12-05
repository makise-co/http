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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RouteDumpCommandTest extends TestCase
{
    public function testItWorks(): void
    {
        putenv('HTTP_WORKER_NUM=1');
        putenv('HTTP_REACTOR_NUM=1');

        $application = new Application(
            dirname(__DIR__) . '/..',
            dirname(__DIR__) . '/../config'
        );

        $command = new CommandTester(
            $application
                ->getContainer()
                ->get(\Symfony\Component\Console\Application::class)
                ->get('routes:dump')
        );

        $command->execute([]);

        $display = $command->getDisplay(true);

        self::assertStringContainsString('GET', $display);
        self::assertStringContainsString('/', $display);
        self::assertStringContainsString('api.', $display);

        self::assertStringContainsString('GET', $display);
        self::assertStringContainsString('/shutdown', $display);
    }
}
