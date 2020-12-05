<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace MakiseCo\Http\Exceptions;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use MakiseCo\Config\ConfigRepositoryInterface;
use MakiseCo\Http\Exceptions\Whoops\FormatNegotiator;
use MakiseCo\Http\Router\Exception\MethodNotAllowedException;
use MakiseCo\Http\Router\Exception\RouteNotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Whoops\Exception\Inspector;
use Whoops\Run;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_LINE_TERMINATORS;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class JsonExceptionHandler extends ExceptionHandler
{
    private Run $whoops;
    private FormatNegotiator $formatNegotiator;

    public function __construct(
        ConfigRepositoryInterface $config,
        LoggerInterface $logger,
        Run $whoops,
        FormatNegotiator $formatNegotiator
    ) {
        parent::__construct($config, $logger);

        $this->whoops = $whoops;
        $this->formatNegotiator = $formatNegotiator;
    }

    protected function renderHttpException(
        ServerRequestInterface $request,
        HttpExceptionInterface $e
    ): ResponseInterface {
        $statusCode = $e->getStatusCode();
        $headers = $e->getHeaders();

        return new Response\JsonResponse(
            ['message' => $e->getMessage()],
            $statusCode,
            $headers,
            $this->getJsonOptions()
        );
    }

    protected function renderThrowable(ServerRequestInterface $request, Throwable $e): ResponseInterface
    {
        if (!$this->config->get('app.debug')) {
            return new JsonResponse(['message' => 'Server Error'], 500);
        }

        $format = $this->formatNegotiator->negotiate($request);

        $handler = $format->getHandler();

        $handler->setRun($this->whoops);
        $handler->setException($e);
        $handler->setInspector(new Inspector($e));

        $content = '';
        ob_start(static function (string $buffer) use (&$content) {
            $content = $buffer;
        });

        try {
            $handler->handle();
        } finally {
            ob_end_clean();
        }

        return new TextResponse(
            $content,
            500,
            [
                'Content-Type' => $format->getPreferredContentType()
            ]
        );
    }

    protected function renderRouteNotFound(
        ServerRequestInterface $request,
        RouteNotFoundException $e
    ): ResponseInterface {
        return new Response\JsonResponse(
            ['message' => 'Not Found'],
            404,
            [],
            $this->getJsonOptions()
        );
    }

    protected function renderMethodNotAllowed(
        ServerRequestInterface $request,
        MethodNotAllowedException $e
    ): ResponseInterface {
        return new Response\JsonResponse(
            ['message' => 'Method Not Allowed'],
            405,
            ['Allow' => $e->getAllowedMethods()],
            $this->getJsonOptions()
        );
    }

    protected function getJsonOptions(): int
    {
        $defaultOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS;

        return $this->config->get('app.debug') ?
            $defaultOptions | JSON_PRETTY_PRINT :
            $defaultOptions;
    }
}
