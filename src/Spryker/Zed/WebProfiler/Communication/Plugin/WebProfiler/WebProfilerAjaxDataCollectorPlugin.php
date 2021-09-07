<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\WebProfiler\Communication\Plugin\WebProfiler;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\WebProfilerExtension\Dependency\Plugin\WebProfilerDataCollectorPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Symfony\Component\HttpKernel\DataCollector\AjaxDataCollector;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

/**
 * @method \Spryker\Zed\WebProfiler\WebProfilerConfig getConfig()
 * @method \Spryker\Zed\WebProfiler\Communication\WebProfilerCommunicationFactory getFactory()
 */
class WebProfilerAjaxDataCollectorPlugin extends AbstractPlugin implements WebProfilerDataCollectorPluginInterface
{
    /**
     * @var string
     */
    protected const NAME = 'ajax';
    /**
     * @var string
     */
    protected const TEMPLATE = '@WebProfiler/Collector/ajax.html.twig';

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
     * - Adds an AjaxDataCollector which collects information about asynchronous requests.
     *
     * @api
     *
     * @param \Spryker\Service\Container\ContainerInterface $container
     *
     * @return \Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface
     */
    public function getDataCollector(ContainerInterface $container): DataCollectorInterface
    {
        return new AjaxDataCollector();
    }
}
