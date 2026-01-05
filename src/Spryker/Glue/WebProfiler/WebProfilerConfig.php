<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\WebProfiler;

use ReflectionClass;
use Spryker\Glue\Kernel\AbstractBundleConfig;
use Spryker\Shared\WebProfiler\WebProfilerConstants;
use Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener;

class WebProfilerConfig extends AbstractBundleConfig
{
    /**
     * @api
     *
     * @return bool
     */
    public function isWebProfilerEnabled(): bool
    {
        return $this->get(WebProfilerConstants::IS_WEB_PROFILER_ENABLED, false);
    }

    /**
     * @api
     *
     * @return array<string>
     */
    public function getWebProfilerTemplatePaths(): array
    {
        $reflectionClass = new ReflectionClass(WebDebugToolbarListener::class);

        return [
            dirname(dirname((string)$reflectionClass->getFileName())) . '/Resources/views',
        ];
    }

    /**
     * @api
     *
     * @return array<string>
     */
    public function getCustomWebProfilerTemplatePaths(): array
    {
        $paths = [
            glob(rtrim(APPLICATION_SOURCE_DIR . '/*/Glue/*/Theme/default/'), GLOB_ONLYDIR | GLOB_NOSORT) ?: [],
            glob(rtrim(APPLICATION_SOURCE_DIR . '/*/*/src/*/Glue/*/Theme/default/'), GLOB_ONLYDIR | GLOB_NOSORT) ?: [],
            glob(rtrim(APPLICATION_ROOT_DIR . '/vendor/*/*/src/*/Glue/*/Theme/default/'), GLOB_ONLYDIR | GLOB_NOSORT) ?: [],
        ];

        return array_merge(...$paths);
    }

    /**
     * @api
     *
     * @return string
     */
    public function getProfilerCacheDirectory(): string
    {
        $defaultPath = APPLICATION_ROOT_DIR . '/data/cache/codeBucket/profiler';

        return $this->get(WebProfilerConstants::PROFILER_CACHE_DIRECTORY, $defaultPath);
    }

    /**
     * @api
     *
     * @return array<string>
     */
    public function getSkipProfilingUriPatterns(): array
    {
        return [
            '/^\/$/',
            '/^\/assets\/.*/',
        ];
    }
}
