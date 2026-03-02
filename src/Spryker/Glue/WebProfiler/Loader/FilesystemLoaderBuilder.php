<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\WebProfiler\Loader;

use Spryker\Glue\WebProfiler\WebProfilerConfig;
use Spryker\Shared\Twig\Loader\FilesystemLoader;
use Spryker\Shared\Twig\Loader\FilesystemLoaderInterface;

class FilesystemLoaderBuilder implements FilesystemLoaderBuilderInterface
{
    protected const WEBPROFILER = 'WebProfiler';

    /**
     * @param array<\Spryker\Shared\WebProfilerExtension\Dependency\Plugin\WebProfilerDataCollectorPluginInterface> $dataCollectorPlugins
     * @param \Spryker\Glue\WebProfiler\WebProfilerConfig $webProfilerConfig
     */
    public function __construct(
        protected array $dataCollectorPlugins,
        protected WebProfilerConfig $webProfilerConfig,
    ) {
    }

    public function createFilesystemLoader(): FilesystemLoaderInterface
    {
        $filesystem = new FilesystemLoader();

        $filesystem->setPaths($this->webProfilerConfig->getWebProfilerTemplatePaths(), static::WEBPROFILER);

        $paths = $this->webProfilerConfig->getCustomWebProfilerTemplatePaths();

        foreach ($this->dataCollectorPlugins as $dataCollectorPlugin) {
            $templateName = $dataCollectorPlugin->getTemplateName();

            $namespace = $this->parseNamespaceFromTemplateName($templateName);

            if ($namespace === null || $namespace === static::WEBPROFILER) {
                continue;
            }

            $matchedPaths = array_filter($paths, function ($path) use ($namespace) {
                return strpos($path, $namespace) !== false;
            });

            foreach ($matchedPaths as $matchedPath) {
                $filesystem->addPath($matchedPath, $namespace);
            }
        }

        return $filesystem;
    }

    protected function parseNamespaceFromTemplateName(string $templateName): ?string
    {
        if (!str_starts_with($templateName, '@')) {
            return null;
        }

        $startPosition = strpos($templateName, '@');

        if ($startPosition === false) {
            return null;
        }

        $endPosition = strpos($templateName, '/');

        if ($endPosition === false) {
            return null;
        }

        return substr($templateName, $startPosition + 1, $endPosition - $startPosition - 1);
    }
}
