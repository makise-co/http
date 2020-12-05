<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace MakiseCo\Http\Tests;

use MakiseCo\Http\StringStream;
use PHPUnit\Framework\TestCase;

class StringStreamTest extends TestCase
{
    private const DEFAULT_CONTENT = 'This is a test!';

    public function testEmptyBody(): void
    {
        $stream = new StringStream('');

        self::assertTrue($stream->eof());
        self::assertSame('', $stream->read(2));
    }

    public function testCrossCase(): void
    {
        $chunkedString = str_pad('', 8192 + 1, '0');

        $stream = new StringStream($chunkedString);
        $read = 0;

        while (!$stream->eof()) {
            $content = $stream->read(8192);
            $read += strlen($content);
        }

        self::assertSame(8192 + 1, $read);
    }

    public function testGetContents(): void
    {
        $stream = new StringStream(self::DEFAULT_CONTENT);
        $content = $stream->getContents();

        self::assertSame(self::DEFAULT_CONTENT, $content);
        self::assertTrue($stream->eof());
    }

    public function testGetContentsReturnsOnlyFromIndexForward(): void
    {
        $stream = new StringStream(self::DEFAULT_CONTENT);

        $index = 10;
        $stream->seek($index);

        self::assertSame(substr(self::DEFAULT_CONTENT, $index), $stream->getContents());
    }
}
