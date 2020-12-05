<?php
/**
 * This file is part of the Makise-Co Framework
 *
 * World line: 0.571024a
 * (c) Dmitry K. <coder1994@gmail.com>
 */

declare(strict_types=1);

namespace MakiseCo\Http;

use DI\Container;
use MakiseCo\Bootstrapper;
use MakiseCo\Config\ConfigRepositoryInterface;
use MakiseCo\Http\Exceptions\JsonExceptionHandler;
use MakiseCo\Http\RequestHandler\RequestHandlerFactory;
use MakiseCo\Http\RequestHandler\RequestHandlerFactoryInterface;
use MakiseCo\Http\Router\RouteCollector;
use MakiseCo\Http\Router\RouteCollectorInterface;
use MakiseCo\Http\Router\RouteCollectorLazyFactory;
use MakiseCo\Http\Swoole\SwooleEmitter;
use MakiseCo\Http\Swoole\SwoolePsrRequestFactory;
use MakiseCo\Http\Swoole\SwoolePsrRequestFactoryInterface;
use MakiseCo\Middleware\ErrorHandlerInterface;
use MakiseCo\Providers\ServiceProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class HttpServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $this->registerErrorHandler($container);
        $this->registerWhoopsFormats($container);
        $this->registerRouter($container);

        $config = $container->get(ConfigRepositoryInterface::class);
        $container->set(
            HttpConfig::class,
            HttpConfig::fromArray($config->get('http', []))
        );

        // register HTTP server
        $container->set(
            HttpServer::class,
            static function (Container $container, ConfigRepositoryInterface $config) {
                return new HttpServer(
                    $container->get(EventDispatcherInterface::class),
                    $container->make(SwoolePsrRequestFactoryInterface::class),
                    $container->make(SwooleEmitter::class),
                    $container->get(RequestHandlerFactoryInterface::class),
                    $container->get(HttpConfig::class),
                    $config->get('app.name', 'makise-co')
                );
            }
        );

        // install services bootstrapper
        $container->get(EventDispatcher::class)->addListener(
            Events\BeforeWorkerStarted::class,
            static function (Events\BeforeWorkerStarted $event) use ($container) {
                $services = $event->getServer()->getConfig()->getServices();

                $container->get(Bootstrapper::class)->init($services);
            }
        );
        $container->get(EventDispatcher::class)->addListener(
            Events\BeforeWorkerExit::class,
            static function (Events\BeforeWorkerExit $event) use ($container) {
                $services = $event->getServer()->getConfig()->getServices();

                $container->get(Bootstrapper::class)->stop($services);
            }
        );
    }

    protected function registerErrorHandler(Container $container): void
    {
        // register JsonExceptionHandler as default exception handler
        $container->set(ErrorHandlerInterface::class, \DI\get(JsonExceptionHandler::class));
    }

    protected function registerWhoopsFormats(Container $container): void
    {
        $container->set(Exceptions\Whoops\FormatNegotiator::class, function (ConfigRepositoryInterface $config) {
            $html = new \Whoops\Handler\PrettyPageHandler();
            $html->handleUnconditionally(true);

            $ignored = (array)$config->get('http.exception_handler.hide', []);
            foreach ($ignored as $superGlobal => $blackListedKeys) {
                foreach ($blackListedKeys as $blackListedKey) {
                    $html->blacklist($superGlobal, $blackListedKey);
                }
            }

            $json = new \Whoops\Handler\JsonResponseHandler();
            $json->addTraceToOutput(true);

            // priority sorted list
            $formats = [
                'json' => new Exceptions\Whoops\Formats\Json($json),
                'html' => new Exceptions\Whoops\Formats\Html($html),
            ];

            return new Exceptions\Whoops\FormatNegotiator($formats, $formats['json']);
        });
    }

    protected function registerRouter(Container $container): void
    {
        // register RequestHandlerFactory as default
        $container->set(RequestHandlerFactoryInterface::class, \DI\get(RequestHandlerFactory::class));

        // register SwoolePsrRequest factory (converts Swoole Http Requests to PSR HTTP Requests)
        $container->set(SwoolePsrRequestFactoryInterface::class, \DI\get(SwoolePsrRequestFactory::class));

        // register route collector
        $container->set(
            RouteCollector::class,
            function (Container $container, ConfigRepositoryInterface $config) {
                $factory = new RouteCollectorLazyFactory(
                    [
                        \Laminas\Diactoros\ServerRequest::class,
                    ]
                );

                $collector = $factory->create($container);

                $this->loadRoutes($config, $collector);

                return $collector;
            }
        );

        // bind route collector implementation to interface
        $container->set(RouteCollectorInterface::class, \DI\get(RouteCollector::class));
    }

    protected function loadRoutes(ConfigRepositoryInterface $config, RouteCollectorInterface $routes): void
    {
        foreach ($config->get('http.routes', []) as $file) {
            include $file;
        }
    }
}
