<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\WebProfiler;

use ReflectionClass;
use Spryker\Shared\WebProfiler\WebProfilerConstants;
use Spryker\Zed\Kernel\AbstractBundleConfig;
use Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener;

class WebProfilerConfig extends AbstractBundleConfig
{
    /**
     * @api
     *
     * @return bool
     */
    public function isWebProfilerEnabled()
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
     * @return string
     */
    public function getProfilerCacheDirectory(): string
    {
        $defaultPath = APPLICATION_ROOT_DIR . '/data/tmp/profiler';

        return $this->get(WebProfilerConstants::PROFILER_CACHE_DIRECTORY, $defaultPath);
    }
}
