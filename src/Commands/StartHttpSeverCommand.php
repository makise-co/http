<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace MakiseCo\Http\Commands;

use MakiseCo\Config\ConfigRepositoryInterface;
use MakiseCo\Console\Commands\AbstractCommand;
use MakiseCo\Http\Events\ServerStarted;
use MakiseCo\Http\HttpServer;
use Psr\Log\LoggerInterface;
use Swoole\Coroutine;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventDispatcher;

class StartHttpSeverCommand extends AbstractCommand
{
    protected string $name = 'http:start';
    protected string $description = 'Starts HTTP server';

    protected array $options = [
        ['host', null, InputOption::VALUE_OPTIONAL, 'Server host', null],
        ['port', 'p', InputOption::VALUE_OPTIONAL, 'Server port', null],
    ];

    public function handle(EventDispatcher $dispatcher, LoggerInterface $logger, HttpServer $server): int
    {
        if (Coroutine::getCid() > 0) {
            $this->error("Please run {$this->getName()} command with \"--no-coroutine\" flag");

            return 1;
        }

        $host = $this->getOption('host');
        if (null === $host) {
            $host = $this
                ->makise
                ->getContainer()
                ->get(ConfigRepositoryInterface::class)
                ->get('http.host', '127.0.0.1');
        }

        $port = $this->getOption('port');
        if (null === $port) {
            $port = $this
                ->makise
                ->getContainer()
                ->get(ConfigRepositoryInterface::class)
                ->get('http.port', 10228);
        } else {
            $port = (int)$port;
        }

        $dispatcher->addListener(
            ServerStarted::class,
            static function () use ($host, $port, $logger) {
                $logger->info('App is started', ['host' => $host, 'port' => $port]);
            }
        );

        $server->start($host, $port);

        $logger->info('App is stopped');

        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getServices(): array
    {
        return [null];
    }
}
