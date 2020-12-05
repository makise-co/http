<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace MakiseCo\Http\Tests\Exceptions;

use InvalidArgumentException;
use Laminas\Diactoros\ServerRequest;
use MakiseCo\Config\ConfigRepositoryInterface;
use MakiseCo\Config\Repository;
use MakiseCo\Http\Exceptions\JsonExceptionHandler;
use MakiseCo\Http\Exceptions\Whoops\FormatNegotiator;
use MakiseCo\Http\Exceptions\Whoops\Formats\Html;
use MakiseCo\Http\Exceptions\Whoops\Formats\Json;
use MakiseCo\Http\StringStream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

use function array_key_exists;

class ExceptionHandlerTest extends TestCase
{
    /**
     * @return LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getFakeLogger(): LoggerInterface
    {
        return $this->createMock(NullLogger::class);
    }

    /**
     * @return ConfigRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getFakeConfig(): ConfigRepositoryInterface
    {
        return $this->createMock(Repository::class);
    }

    protected function getFakeRequest(string $method, string $uri, array $headers = []): ServerRequestInterface
    {
        return new ServerRequest(
            [
                'REQUEST_METHOD' => $method,
                'REQUEST_URI' => $uri
            ],
            [],
            $uri,
            $method,
            new StringStream(''),
            $headers
        );
    }

    /**
     * @testdox Check that the ExceptionHandler is logging request method and request URI
     */
    public function testLoggingRequestInfo(): void
    {
        $config = $this->getFakeConfig();
        $config
            ->method('get')
            ->with('app.debug')
            ->willReturn(true);

        $method = 'GET';
        $uri = '/makise?some=1';
        $message = 'Something went wrong';

        $logger = $this->getFakeLogger();
        $logger
            ->expects(self::once())
            ->method('error')
            ->with(
                $message,
                self::callback(
                    static function (array $args) use ($method, $uri) {
                        if (!array_key_exists('extra', $args)) {
                            return false;
                        }

                        $extra = $args['extra'];

                        if (!array_key_exists('uri', $extra) || $uri !== $extra['uri']) {
                            return false;
                        }

                        if (!array_key_exists('method', $extra) || $method !== $extra['method']) {
                            return false;
                        }

                        return true;
                    }
                )
            );

        $request = $this->getFakeRequest($method, $uri);
        $exception = new InvalidArgumentException($message);

        $handler = $this->getExceptionHandler($config, $logger);
        $handler->handle($exception, $request);
    }

    public function testPrettyPageResponse(): void
    {
        $config = $this->getFakeConfig();
        $config
            ->method('get')
            ->with('app.debug')
            ->willReturn(true);

        $logger = $this->getFakeLogger();

        $message = 'Something went wrong';

        $request = $this->getFakeRequest(
            'GET',
            '/makise?some=1',
            [
                'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,' .
                    '*/*;q=0.8,application/signed-exchange;v=b3;q=0.9'
            ]
        );
        $exception = new InvalidArgumentException($message);

        $handler = $this->getExceptionHandler($config, $logger);
        $response = $handler->handle($exception, $request);

        self::assertSame('text/html; charset=UTF-8', $response->getHeaderLine('content-type'));

        $body = $response->getBody()->getContents();

        self::assertStringContainsString(
            'MakiseCo\Http\Tests\Exceptions\ExceptionHandlerTest:testPrettyPageResponse in',
            $body
        );
        self::assertStringContainsString('$request = ', $body);
    }

    public function testJsonResponse(): void
    {
        $config = $this->getFakeConfig();
        $config
            ->method('get')
            ->with('app.debug')
            ->willReturn(true);

        $logger = $this->getFakeLogger();

        $message = 'Something went wrong';

        $request = $this->getFakeRequest(
            'GET',
            '/makise?some=1',
            ['accept' => 'application/json']
        );
        $exception = new InvalidArgumentException($message);

        $handler = $this->getExceptionHandler($config, $logger);
        $response = $handler->handle($exception, $request);

        self::assertSame('application/json; charset=UTF-8', $response->getHeaderLine('content-type'));

        $body = $response->getBody()->getContents();
        $decoded = \json_decode($body, true, 512, \JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('error', $decoded);
        self::assertArrayHasKey('type', $decoded['error']);
        self::assertArrayHasKey('message', $decoded['error']);
        self::assertSame(InvalidArgumentException::class, $decoded['error']['type']);
        self::assertSame($message, $decoded['error']['message']);
    }

    public function testDefaultResponse(): void
    {
        $config = $this->getFakeConfig();
        $config
            ->method('get')
            ->with('app.debug')
            ->willReturn(true);

        $logger = $this->getFakeLogger();

        $request = $this->getFakeRequest('GET', '/makise?some=1');
        $exception = new InvalidArgumentException('Something went wrong');

        $handler = $this->getExceptionHandler($config, $logger);
        $response = $handler->handle($exception, $request);

        self::assertSame('application/json; charset=UTF-8', $response->getHeaderLine('content-type'));
    }

    protected function getExceptionHandler(
        ConfigRepositoryInterface $config,
        LoggerInterface $logger
    ): JsonExceptionHandler {
        $html = new PrettyPageHandler();
        $html->handleUnconditionally(true);

        $formats = [
            'json' => new Json(new JsonResponseHandler()),
            'html' => new Html($html),
        ];

        $formatNegotiator = new FormatNegotiator($formats, $formats['json']);

        return new JsonExceptionHandler($config, $logger, new Run(), $formatNegotiator);
    }
}
