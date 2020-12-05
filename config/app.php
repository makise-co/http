<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

return [
    'name' => 'makise-co',

    'providers' => [
        \MakiseCo\Log\LoggerServiceProvider::class,
        \MakiseCo\Event\EventDispatcherServiceProvider::class,
        \MakiseCo\Console\ConsoleServiceProvider::class,
        \MakiseCo\Http\HttpServiceProvider::class,
    ],

    'commands' => [
        \MakiseCo\Http\Commands\StartHttpSeverCommand::class,
        \MakiseCo\Http\Commands\RoutesDumpCommand::class,
    ],
];
