<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace MakiseCo\Http\Events;

use MakiseCo\Http\HttpServer;

/**
 * @internal This event is used to stop app services, for your purposes please use WorkerExit event
 */
class BeforeWorkerExit
{
    private int $workerId;
    private HttpServer $server;

    public function __construct(int $workerId, HttpServer $server)
    {
        $this->workerId = $workerId;
        $this->server = $server;
    }

    public function getWorkerId(): int
    {
        return $this->workerId;
    }

    public function getServer(): HttpServer
    {
        return $this->server;
    }
}
