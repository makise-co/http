<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace MakiseCo\Http\Tests\Testing;

use DI\Container;
use MakiseCo\Http\RequestHandler\RequestHandlerFactoryInterface;
use MakiseCo\Http\Testing\MakesHttpRequests;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MakesRequestTraitTest extends TestCase
{
    use MakesHttpRequests;

    protected Container $container;
    /**
     * @var RequestHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected RequestHandlerInterface $requestHandlerMock;

    protected function setUp(): void
    {
        $this->container = new Container();

        $this->requestHandlerMock = $this->createMock(RequestHandlerInterface::class);

        $this->container->set(RequestHandlerFactoryInterface::class, function () {
            $factoryMock = $this->createMock(RequestHandlerFactoryInterface::class);
            $factoryMock
                ->method('create')
                ->willReturn($this->requestHandlerMock);

            return $factoryMock;
        });
    }

    public function testGet(): void
    {
        $this
            ->requestHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with(self::callback(function (ServerRequestInterface $request) {
                return '/some' === $request->getRequestTarget() && 'GET' === $request->getMethod()
                    && count($request->getHeaders()) === 1 && $request->getHeader('Authorization') === ['Bearer 123'];
            }));

        $this->get('/some', ['Authorization' => 'Bearer 123']);
    }

    public function testPost(): void
    {
        $this
            ->requestHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with(self::callback(function (ServerRequestInterface $request) {
                return '/some' === $request->getRequestTarget()
                    && 'POST' === $request->getMethod()
                    && 1 === $request->getParsedBody()['some'];
            }));

        $this->post('/some', ['some' => 1]);
    }

    public function testJson(): void
    {
        $this
            ->requestHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with(self::callback(function (ServerRequestInterface $request) {
                return '/some' === $request->getRequestTarget()
                    && 'POST' === $request->getMethod()
                    && \json_encode(['some' => 1]) === $request->getBody()->__toString()
                    && ['some' => 1] === $request->getParsedBody();
            }));

        $this->postJson('/some', ['some' => 1]);
    }
}
