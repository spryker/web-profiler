<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Glue\WebProfiler\Plugin\Application;

use Codeception\Test\Unit;
use Spryker\Glue\WebProfiler\Plugin\Application\WebProfilerApplicationPlugin;
use Spryker\Service\Container\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group WebProfiler
 * @group Plugin
 * @group Application
 * @group WebProfilerApplicationPluginTest
 * Add your own group annotations below this line
 */
class WebProfilerApplicationPluginTest extends Unit
{
    /**
     * @uses \Symfony\Bundle\WebProfilerBundle\Controller\ProfilerController::fontAction()
     */
    protected const string ROUTE_NAME_PROFILER_FONT = '_profiler_font';

    public function testRouterGeneratesProfilerFontRouteUsedByProfilerCssTemplate(): void
    {
        // Arrange
        $plugin = $this->createTestDouble();
        $containerMock = $this->createMock(ContainerInterface::class);

        // Act
        $router = $plugin->testGetRouter($containerMock);
        $url = $router->generate(static::ROUTE_NAME_PROFILER_FONT, ['fontName' => 'JetBrainsMono']);

        // Assert
        $this->assertSame('/_profiler/font/JetBrainsMono.woff2', $url);
    }

    protected function createTestDouble(): object
    {
        return new class extends WebProfilerApplicationPlugin {
            public function testGetRouter(ContainerInterface $container): RouterInterface
            {
                return $this->getRouter($container);
            }
        };
    }
}
