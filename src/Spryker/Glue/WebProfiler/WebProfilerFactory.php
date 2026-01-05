<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\WebProfiler;

use Spryker\Glue\Kernel\AbstractFactory;
use Spryker\Glue\WebProfiler\Loader\FilesystemLoaderBuilder;
use Spryker\Glue\WebProfiler\Loader\FilesystemLoaderBuilderInterface;
use Spryker\Shared\Twig\Loader\FilesystemLoaderInterface;
use Symfony\Component\Form\Extension\DataCollector\FormDataCollector;
use Symfony\Component\Form\Extension\DataCollector\FormDataCollectorInterface;
use Symfony\Component\Form\Extension\DataCollector\FormDataExtractor;
use Symfony\Component\Form\Extension\DataCollector\FormDataExtractorInterface;
use Symfony\Component\Form\Extension\DataCollector\Type\DataCollectorTypeExtension;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\HttpKernel\Profiler\FileProfilerStorage;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\HttpKernel\Profiler\ProfilerStorageInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Twig\Profiler\Profile;

/**
 * @method \Spryker\Glue\WebProfiler\WebProfilerConfig getConfig()
 */
class WebProfilerFactory extends AbstractFactory
{
    /**
     * @return \Spryker\Shared\Twig\Loader\FilesystemLoaderInterface
     */
    public function createTwigFilesystemLoader(): FilesystemLoaderInterface
    {
        return $this->createFilesystemLoaderBuilder()->createFilesystemLoader();
    }

    /**
     * @return \Spryker\Glue\WebProfiler\Loader\FilesystemLoaderBuilderInterface
     */
    public function createFilesystemLoaderBuilder(): FilesystemLoaderBuilderInterface
    {
        return new FilesystemLoaderBuilder(
            $this->getDataCollectorPlugins(),
            $this->getConfig(),
        );
    }

    /**
     * @return array<\Spryker\Shared\WebProfilerExtension\Dependency\Plugin\WebProfilerDataCollectorPluginInterface>
     */
    public function getDataCollectorPlugins(): array
    {
        return $this->getProvidedDependency(WebProfilerDependencyProvider::PLUGINS_DATA_COLLECTORS);
    }

    /**
     * @return \Symfony\Component\Stopwatch\Stopwatch
     */
    public function createStopwatch(): Stopwatch
    {
        return new Stopwatch();
    }

    /**
     * @return \Symfony\Component\HttpKernel\Profiler\Profiler
     */
    public function createProfiler(): Profiler
    {
        return new Profiler($this->createProfilerStorage());
    }

    /**
     * @return \Symfony\Component\HttpKernel\Profiler\ProfilerStorageInterface
     */
    public function createProfilerStorage(): ProfilerStorageInterface
    {
        return new FileProfilerStorage('file:' . $this->getConfig()->getProfilerCacheDirectory());
    }

    /**
     * @return \Twig\Profiler\Profile
     */
    public function createProfile(): Profile
    {
        return new Profile();
    }

    /**
     * @return \Symfony\Component\Form\Extension\DataCollector\FormDataCollectorInterface
     */
    public function createFormDataCollector(): FormDataCollectorInterface
    {
        return new FormDataCollector($this->createFormDataExtractor());
    }

    /**
     * @return \Symfony\Component\Form\Extension\DataCollector\FormDataExtractorInterface
     */
    public function createFormDataExtractor(): FormDataExtractorInterface
    {
        return new FormDataExtractor();
    }

    /**
     * @return \Symfony\Component\Form\FormTypeExtensionInterface
     */
    public function createDataCollectorTypeExtension(): FormTypeExtensionInterface
    {
        return new DataCollectorTypeExtension($this->createFormDataCollector());
    }

    /**
     * @return array<\Spryker\Shared\TwigExtension\Dependency\Plugin\TwigPluginInterface>
     */
    public function getTwigPlugins(): array
    {
        return $this->getProvidedDependency(WebProfilerDependencyProvider::PLUGINS_TWIG);
    }
}
