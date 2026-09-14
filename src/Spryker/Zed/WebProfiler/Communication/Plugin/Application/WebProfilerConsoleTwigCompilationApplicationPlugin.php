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
     * @var class-string<\Twig\Extension\ExtensionInterface>
     *
     * @uses \Symfony\Bundle\WebProfilerBundle\Profiler\CodeExtension Present since symfony/web-profiler-bundle 7.x.
     */
    protected const string CODE_EXTENSION_CLASS_WEB_PROFILER_BUNDLE = 'Symfony\Bundle\WebProfilerBundle\Profiler\CodeExtension';

    /**
     * @var class-string<\Twig\Extension\ExtensionInterface>
     *
     * @uses \Symfony\Bridge\Twig\Extension\CodeExtension Present in symfony/twig-bridge up to 6.4, internal from 6.4 on.
     */
    protected const string CODE_EXTENSION_CLASS_TWIG_BRIDGE = 'Symfony\Bridge\Twig\Extension\CodeExtension';

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

            $codeExtensionClassName = $this->resolveCodeExtensionClassName();

            // @phpstan-ignore new.internalClass, method.internalClass
            $twig->addExtension(new $codeExtensionClassName(new FileLinkFormatter(null), '', static::DEFAULT_CHARSET));
            // @phpstan-ignore new.internalClass, method.internalClass
            $twig->addExtension(new WebProfilerExtension());
            $twig->addExtension(new ProfilerExtension(new Profile(static::PROFILE_TOKEN), new Stopwatch()));

            return $twig;
        });

        return $container;
    }

    /**
     * @return class-string<\Twig\Extension\ExtensionInterface>
     */
    protected function resolveCodeExtensionClassName(): string
    {
        if (class_exists(static::CODE_EXTENSION_CLASS_WEB_PROFILER_BUNDLE)) {
            return static::CODE_EXTENSION_CLASS_WEB_PROFILER_BUNDLE;
        }

        return static::CODE_EXTENSION_CLASS_TWIG_BRIDGE;
    }
}
