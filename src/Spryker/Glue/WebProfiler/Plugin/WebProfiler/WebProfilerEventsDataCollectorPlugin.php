<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\WebProfiler\Plugin\WebProfiler;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\WebProfiler\DataCollector\WebProfilerEventDataCollector;
use Spryker\Shared\WebProfilerExtension\Dependency\Plugin\WebProfilerDataCollectorPluginInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

class WebProfilerEventsDataCollectorPlugin implements WebProfilerDataCollectorPluginInterface
{
    protected const string SERVICE_DISPATCHER = 'dispatcher';

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
     * - Adds a WebProfilerEventDataCollector which collects information about the triggered events.
     *
     * @api
     *
     * @param \Spryker\Service\Container\ContainerInterface $container
     *
     * @return \Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface
     */
    public function getDataCollector(ContainerInterface $container): DataCollectorInterface
    {
        return new WebProfilerEventDataCollector($container->get(static::SERVICE_DISPATCHER));
    }
}
