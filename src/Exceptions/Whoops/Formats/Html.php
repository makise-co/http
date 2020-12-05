<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

namespace MakiseCo\Http\Exceptions\Whoops\Formats;

use Whoops\Handler\HandlerInterface;

class Html implements Format
{
    public const MIMES = ['text/html', 'application/xhtml+xml'];

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
        return 'text/html; charset=UTF-8';
    }
}
