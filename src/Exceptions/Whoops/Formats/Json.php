<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

namespace MakiseCo\Http\Exceptions\Whoops\Formats;

use Whoops\Handler\HandlerInterface;
use Whoops\Handler\JsonResponseHandler;

class Json implements Format
{
    public const MIMES = ['application/json', 'text/json', 'application/x-json'];

    protected HandlerInterface $handler;

    public function __construct(HandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    public function getHandler(): HandlerInterface
    {
        return $this->handler;
    }

    public function getMimes(): array
    {
        return self::MIMES;
    }

    public function getPreferredContentType(): string
    {
        return 'application/json; charset=UTF-8';
    }
}
