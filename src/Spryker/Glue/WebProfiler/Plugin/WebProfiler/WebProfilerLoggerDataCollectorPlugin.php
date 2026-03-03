<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\WebProfiler\Plugin\WebProfiler;

use Monolog\Logger as MonologLogger;
use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Shared\WebProfilerExtension\Dependency\Plugin\WebProfilerDataCollectorPluginInterface;
use Symfony\Bridge\Monolog\Processor\DebugProcessor;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\DataCollector\LoggerDataCollector;

class WebProfilerLoggerDataCollectorPlugin implements WebProfilerDataCollectorPluginInterface
{
    use LoggerTrait;

    /**
     * @var string
     */
    protected const NAME = 'logger';

    /**
     * @var string
     */
    protected const TEMPLATE = '@WebProfiler/Collector/logger.html.twig';

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
     * - Adds a LoggerDataCollector which collects data from the logger.
     *
     * @api
     *
     * @param \Spryker\Service\Container\ContainerInterface $container
     *
     * @return \Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface
     */
    public function getDataCollector(ContainerInterface $container): DataCollectorInterface
    {
        $logger = $this->getLogger();

        if ($logger instanceof MonologLogger) {
            $logger->pushProcessor(new DebugProcessor());
        }

        return new LoggerDataCollector($logger);
    }
}
