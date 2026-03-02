<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\WebProfiler\Plugin\Application;

use Spryker\Glue\Kernel\AbstractPlugin;
use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\ApplicationExtension\Dependency\Plugin\ApplicationPluginInterface;
use Spryker\Shared\ApplicationExtension\Dependency\Plugin\BootableApplicationPluginInterface;
use Spryker\Shared\EventDispatcher\EventDispatcherInterface;
use Spryker\Shared\WebProfiler\UrlGenerator\WebDebugToolbarUrlGenerator;
use Symfony\Bridge\Twig\Extension\CodeExtension;
use Symfony\Bridge\Twig\Extension\ProfilerExtension;
use Symfony\Bundle\WebProfilerBundle\Controller\ExceptionPanelController;
use Symfony\Bundle\WebProfilerBundle\Controller\ProfilerController;
use Symfony\Bundle\WebProfilerBundle\Controller\RouterController;
use Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener;
use Symfony\Bundle\WebProfilerBundle\Twig\WebProfilerExtension;
use Symfony\Cmf\Component\Routing\ChainRouter;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\HttpKernel\Debug\FileLinkFormatter;
use Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\EventListener\ProfilerListener;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Loader\ClosureLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Loader\ChainLoader;

/**
 * @method \Spryker\Glue\WebProfiler\WebProfilerConfig getConfig()
 * @method \Spryker\Glue\WebProfiler\WebProfilerFactory getFactory()
 */
class WebProfilerApplicationPlugin extends AbstractPlugin implements ApplicationPluginInterface, BootableApplicationPluginInterface
{
    /**
     * @var string
     */
    public const SERVICE_STOPWATCH = 'stopwatch';

    /**
     * @var string
     */
    public const SERVICE_LOGGER = 'logger';

    /**
     * @var string
     */
    public const SERVICE_PROFILER = 'profiler';

    /**
     * @var string
     */
    public const SERVICE_DISPATCHER = 'dispatcher';

    /**
     * @var string
     */
    public const SERVICE_TWIG_PROFILE = 'profile';

    /**
     * @var string
     */
    public const SERVICE_REQUEST = 'request';

    /**
     * @var string
     */
    public const SERVICE_REQUEST_STACK = 'request_stack';

    /**
     * @var string
     */
    public const SERVICE_ROUTER = 'routers';

    /**
     * @var string
     */
    public const SERVICE_CHARSET = 'charset';

    /**
     * @var int
     */
    protected const ROUTER_PRIORITY = 10;

    /**
     * @var int
     */
    protected const CONTROLLER_EVENT_PRIORITY = 1000;

    /**
     * @var \Twig\Environment|null
     */
    protected ?Environment $twig = null;

    /**
     * {@inheritDoc}
     * - Provides a WebProfiler which collects data from WebProfilerDataCollectorPluginInterface's and adds a toolbar at the bottom opf the page.
     *
     * @api
     *
     * @param \Spryker\Service\Container\ContainerInterface $container
     *
     * @return \Spryker\Service\Container\ContainerInterface
     */
    public function provide(ContainerInterface $container): ContainerInterface
    {
        if ($this->shouldSkipWebProfiler()) {
            return $container;
        }

        $container = $this->extendEventDispatcher($container);
        $container = $this->extendRouter($container);

        $container->set(static::SERVICE_STOPWATCH, function () {
            return $this->getFactory()->createStopwatch();
        });

        $container->set(static::SERVICE_PROFILER, function (ContainerInterface $container) {
            $profiler = $this->getFactory()->createProfiler();
            $profiler = $this->addDataCollectorPlugins($profiler, $container);

            return $profiler;
        });

        $container->set(static::SERVICE_TWIG_PROFILE, function () {
            return $this->getFactory()->createProfile();
        });

        return $container;
    }

    protected function extendEventDispatcher(ContainerInterface $container): ContainerInterface
    {
        $container->extend(static::SERVICE_DISPATCHER, function (EventDispatcherInterface $dispatcher, ContainerInterface $container) {
            return new TraceableEventDispatcher($dispatcher, $container->get(static::SERVICE_STOPWATCH), $container->get(static::SERVICE_LOGGER));
        });

        return $container;
    }

    protected function extendRouter(ContainerInterface $container): ContainerInterface
    {
        $container->extend(static::SERVICE_ROUTER, function (ChainRouter $chainRouter, ContainerInterface $container) {
            $chainRouter->add($this->getRouter($container), static::ROUTER_PRIORITY);

            return $chainRouter;
        });

        return $container;
    }

    protected function getTwigEnvironment(ContainerInterface $container): Environment
    {
        if ($this->twig !== null) {
            return $this->twig;
        }

        $twig = new Environment($this->getFactory()->createTwigFilesystemLoader());

        foreach ($this->getFactory()->getTwigPlugins() as $plugin) {
            $twig = $plugin->extend($twig, $container);
        }

        $fileLinkFormatter = new FileLinkFormatter(null);
        $twig->addExtension(new CodeExtension($fileLinkFormatter, '', $container->get(static::SERVICE_CHARSET)));
        $twig->addExtension(new WebProfilerExtension());
        $twig->addExtension(new ProfilerExtension($container->get(static::SERVICE_TWIG_PROFILE), $container->get(static::SERVICE_STOPWATCH)));

        $loader = $twig->getLoader();

        if ($loader instanceof ChainLoader) {
            $loader->addLoader($this->getFactory()->createTwigFilesystemLoader());
        }

        $this->twig = $twig;

        return $this->twig;
    }

    protected function addDataCollectorPlugins(Profiler $profiler, ContainerInterface $container): Profiler
    {
        foreach ($this->getFactory()->getDataCollectorPlugins() as $dataCollectorPlugin) {
            $profiler->add($dataCollectorPlugin->getDataCollector($container));
        }

        return $profiler;
    }

    protected function getRouter(ContainerInterface $container): RouterInterface
    {
        $loader = new ClosureLoader();

        $resource = function () use ($container) {
            $routeCollection = new RouteCollection();
            foreach ($this->getRouteDefinitions($container) as $routeDefinition) {
                [$pathinfo, $controller, $routeName] = $routeDefinition;

                $route = new Route($pathinfo);
                $route->setMethods('GET');
                $route->setDefault('_controller', $controller);

                $routeCollection->add($routeName, $route, 0);
            }

            return $routeCollection;
        };

        return new Router($loader, $resource, []);
    }

    /**
     * @param \Spryker\Service\Container\ContainerInterface $container
     *
     * @return array<mixed>
     */
    protected function getRouteDefinitions(ContainerInterface $container): array
    {
        $profilerController = function () use ($container) {
            return new ProfilerController(
                $container->get(static::SERVICE_ROUTER),
                $container->get(static::SERVICE_PROFILER),
                $this->getTwigEnvironment($container),
                $this->getDataCollectorPluginTemplates(),
            );
        };

        $routerController = function () use ($container) {
            return new RouterController(
                $container->get(static::SERVICE_PROFILER),
                $this->getTwigEnvironment($container),
                $container->get(static::SERVICE_ROUTER),
            );
        };

        $exceptionController = function () use ($container) {
            return new ExceptionPanelController(
                new HtmlErrorRenderer($container->get('debug')),
                $container->get(static::SERVICE_PROFILER),
            );
        };

        return [
            ['/_profiler/router/{token}', [$routerController, 'panelAction'], '_profiler_router'],
            ['/_profiler/exception/{token}.css', [$exceptionController, 'cssAction'], '_profiler_exception_css'],
            ['/_profiler/exception/{token}', [$exceptionController, 'showAction'], '_profiler_exception'],
            ['/_profiler/search', [$profilerController, 'searchAction'], '_profiler_search'],
            ['/_profiler/search_bar', [$profilerController, 'searchBarAction'], '_profiler_search_bar'],
            ['/_profiler/purge', [$profilerController, 'purgeAction'], '_profiler_purge'],
            ['/_profiler/info/{about}', [$profilerController, 'infoAction'], '_profiler_info'],
            ['/_profiler/phpinfo', [$profilerController, 'phpinfoAction'], '_profiler_phpinfo'],
            ['/_profiler/{token}/search/results', [$profilerController, 'searchResultsAction'], '_profiler_search_results'],
            ['/_profiler/{token}', [$profilerController, 'panelAction'], '_profiler'],
            ['/_profiler/wdt/{token}', [$profilerController, 'toolbarAction'], '_wdt'],
            ['/_profiler/', [$profilerController, 'homeAction'], '_profiler_home'],
        ];
    }

    /**
     * @return array<array<string>>
     */
    protected function getDataCollectorPluginTemplates(): array
    {
        $dataCollectorTemplates = [];
        foreach ($this->getFactory()->getDataCollectorPlugins() as $dataCollectorPlugin) {
            $dataCollectorTemplates[] = [
                $dataCollectorPlugin->getName(),
                $dataCollectorPlugin->getTemplateName(),
            ];
        }

        return $dataCollectorTemplates;
    }

    /**
     * {@inheritDoc}
     * - Adds subscriber to the EventDispatcher when the WebProfiler is enabled.
     *
     * @api
     *
     * @param \Spryker\Service\Container\ContainerInterface $container
     *
     * @return \Spryker\Service\Container\ContainerInterface
     */
    public function boot(ContainerInterface $container): ContainerInterface
    {
        if ($this->shouldSkipWebProfiler()) {
            return $container;
        }

        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
        $dispatcher = $container->get(static::SERVICE_DISPATCHER);
        /** @var \Symfony\Component\HttpKernel\Profiler\Profiler $profilerService */
        $profilerService = $container->get(static::SERVICE_PROFILER);
        /** @var \Symfony\Component\HttpFoundation\RequestStack $requestStack */
        $requestStack = $container->get(static::SERVICE_REQUEST_STACK);

        $dispatcher->addSubscriber(new ProfilerListener($profilerService, $requestStack, null, false, false));
        $dispatcher->addSubscriber(new WebDebugToolbarListener(
            $this->getTwigEnvironment($container),
            false,
            WebDebugToolbarListener::ENABLED,
            new WebDebugToolbarUrlGenerator(),
        ));

        /** @var \Symfony\Component\EventDispatcher\EventSubscriberInterface $requestService */
        $requestService = $profilerService->get(static::SERVICE_REQUEST);
        $dispatcher->addSubscriber($requestService);

        $dispatcher->addListener(KernelEvents::CONTROLLER, function (ControllerEvent $event) {
            $this->onKernelController($event);
        }, static::CONTROLLER_EVENT_PRIORITY);

        /**
         * Enable XHProf for the ProfilerRequestEventDispatcherPlugin.
         * @link \Spryker\Glue\Profiler\Plugin\EventDispatcher\ProfilerRequestEventDispatcherPlugin
         */
        if (extension_loaded('xhprof')) {
            xhprof_enable(XHPROF_FLAGS_NO_BUILTINS);
        }

        return $container;
    }

    protected function onKernelController(ControllerEvent $event): void
    {
        $currentController = $event->getController();

        if (!is_array($currentController) || count($currentController) !== 2) {
            return;
        }

        [$controller, $action] = $currentController;
        $request = $event->getRequest();

        if ($controller instanceof ProfilerController) {
            $event->setController(function () use ($controller, $action, $request) {
                return $controller->$action($request, $request->attributes->get('token'));
            });
        }
    }

    protected function shouldSkipWebProfiler(): bool
    {
        if (!$this->getConfig()->isWebProfilerEnabled()) {
            return true;
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? null;

        if ($requestUri === null) {
            return false;
        }

        return $this->matchesSkipUriPattern($requestUri);
    }

    protected function matchesSkipUriPattern(string $requestUri): bool
    {
        foreach ($this->getConfig()->getSkipProfilingUriPatterns() as $pattern) {
            if (preg_match($pattern, $requestUri)) {
                return true;
            }
        }

        return false;
    }
}
