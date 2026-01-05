<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\WebProfiler;

use Spryker\Glue\Kernel\AbstractBundleDependencyProvider;
use Spryker\Glue\Kernel\Container;

/**
 * @method \Spryker\Glue\WebProfiler\WebProfilerConfig getConfig()
 */
class WebProfilerDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @var string
     */
    public const PLUGINS_DATA_COLLECTORS = 'PLUGINS_DATA_COLLECTORS';

    /**
     * @var string
     */
    public const PLUGINS_TWIG = 'PLUGINS_TWIG';

    /**
     * @param \Spryker\Glue\Kernel\Container $container
     *
     * @return \Spryker\Glue\Kernel\Container
     */
    public function provideDependencies(Container $container): Container
    {
        $container = $this->addDataCollectorPlugins($container);
        $container = $this->addTwigPlugins($container);

        return $container;
    }

    /**
     * @param \Spryker\Glue\Kernel\Container $container
     *
     * @return \Spryker\Glue\Kernel\Container
     */
    protected function addDataCollectorPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_DATA_COLLECTORS, function () {
            return $this->getDataCollectorPlugins();
        });

        return $container;
    }

    /**
     * @return array<\Spryker\Shared\WebProfilerExtension\Dependency\Plugin\WebProfilerDataCollectorPluginInterface>
     */
    protected function getDataCollectorPlugins(): array
    {
        return [];
    }

    /**
     * @param \Spryker\Glue\Kernel\Container $container
     *
     * @return \Spryker\Glue\Kernel\Container
     */
    protected function addTwigPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_TWIG, function () {
            return $this->getTwigPlugins();
        });

        return $container;
    }

    /**
     * @return array<\Spryker\Shared\TwigExtension\Dependency\Plugin\TwigPluginInterface>
     */
    protected function getTwigPlugins(): array
    {
        return [];
    }
}
