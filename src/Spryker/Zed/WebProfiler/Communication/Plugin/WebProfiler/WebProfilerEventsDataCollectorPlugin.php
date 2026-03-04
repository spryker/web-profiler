<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\WebProfiler\Communication\Plugin\WebProfiler;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\WebProfiler\DataCollector\WebProfilerEventDataCollector;
use Spryker\Shared\WebProfilerExtension\Dependency\Plugin\WebProfilerDataCollectorPluginInterface;
use Spryker\Zed\EventDispatcher\Communication\Plugin\Application\EventDispatcherApplicationPlugin;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

class WebProfilerEventsDataCollectorPlugin implements WebProfilerDataCollectorPluginInterface
{
    protected const string NAME = 'events';

    protected const string TEMPLATE = '@WebProfiler/Collector/events.html.twig';

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return string
     */
    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return static::TEMPLATE;
    }

    /**
     * {@inheritDoc}
     * - Adds a WebProfilerEventDataCollector which collects information about triggered events.
     *
     * @api
     *
     * @param \Spryker\Service\Container\ContainerInterface $container
     *
     * @return \Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface
     */
    public function getDataCollector(ContainerInterface $container): DataCollectorInterface
    {
        return new WebProfilerEventDataCollector($container->get(EventDispatcherApplicationPlugin::SERVICE_DISPATCHER));
    }
}
