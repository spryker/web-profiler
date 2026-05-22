<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace Spryker\Zed\WebProfiler\Communication\Plugin\Application;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\ApplicationExtension\Dependency\Plugin\ApplicationPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Symfony\Bridge\Twig\Extension\CodeExtension;
use Symfony\Bridge\Twig\Extension\ProfilerExtension;
use Symfony\Bundle\WebProfilerBundle\Twig\WebProfilerExtension;
use Symfony\Component\ErrorHandler\ErrorRenderer\FileLinkFormatter;
use Symfony\Component\Stopwatch\Stopwatch;
use Twig\Environment;
use Twig\Profiler\Profile;

/**
 * @method \Spryker\Zed\WebProfiler\WebProfilerConfig getConfig()
 */
class WebProfilerConsoleTwigCompilationApplicationPlugin extends AbstractPlugin implements ApplicationPluginInterface
{
    /**
     * @uses \Spryker\Zed\Twig\Communication\Plugin\Application\TwigApplicationPlugin::SERVICE_TWIG
     */
    protected const string SERVICE_TWIG = 'twig';

    protected const string DEFAULT_CHARSET = 'UTF-8';

    protected const string PROFILE_TOKEN = 'twig-warmer';

    /**
     * {@inheritDoc}
     * - Registers WebProfiler-related Twig extensions on the console Twig environment so that profiler templates
     *   (e.g. `@WebProfiler/Collector/session.html.twig`, `@Log/Collector/audit_log.html.twig`) can be compiled by
     *   `twig:template:warmer` even when the WebProfiler runtime flag (`WEB_PROFILER:IS_WEB_PROFILER_ENABLED`) is off.
     * - No-ops when the WebProfiler runtime flag is on (the runtime profiler already registers these extensions),
     *   or when the extensions are already present on the Twig environment.
     *
     * @api
     */
    public function provide(ContainerInterface $container): ContainerInterface
    {
        // @phpstan-ignore classConstant.internalClass
        if (class_exists(WebProfilerExtension::class) === false) {
            return $container;
        }

        $container->extend(static::SERVICE_TWIG, function (Environment $twig): Environment {
            // @phpstan-ignore classConstant.internalClass
            if ($twig->hasExtension(WebProfilerExtension::class)) {
                return $twig;
            }

            // @phpstan-ignore new.internalClass, method.internalClass
            $twig->addExtension(new CodeExtension(new FileLinkFormatter(null), '', static::DEFAULT_CHARSET));
            // @phpstan-ignore new.internalClass, method.internalClass
            $twig->addExtension(new WebProfilerExtension());
            $twig->addExtension(new ProfilerExtension(new Profile(static::PROFILE_TOKEN), new Stopwatch()));

            return $twig;
        });

        return $container;
    }
}
