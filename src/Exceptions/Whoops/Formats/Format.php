<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

namespace MakiseCo\Http\Exceptions\Whoops\Formats;

use Whoops\Handler\HandlerInterface;

interface Format
{
    public function getHandler(): HandlerInterface;

    /**
     * @return string[]
     */
    public function getMimes(): array;

    public function getPreferredContentType(): string;
}
