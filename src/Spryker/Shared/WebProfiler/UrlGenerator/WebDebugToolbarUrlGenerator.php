<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\WebProfiler\UrlGenerator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class WebDebugToolbarUrlGenerator implements UrlGeneratorInterface
{
    protected RequestContext $context;

    public function __construct(?RequestContext $context = null)
    {
        if ($context === null) {
            $context = (new RequestContext())
                ->fromRequest(Request::createFromGlobals());
        }

        $this->context = $context;
    }

    /**
     * @param array<string, string> $parameters
     */
    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        $pathSegments = [rawurlencode($name)];

        if (!empty($parameters['token'])) {
            // $parameters['token'] - profile identity X-Debug-Token
            $pathSegments[] = rawurlencode($parameters['token']);
        }

        $path = implode('/', $pathSegments);

        return sprintf('%s://%s/%s', $this->context->getScheme(), $this->context->getHost(), $path);
    }

    public function getContext(): RequestContext
    {
        return $this->context;
    }

    public function setContext(RequestContext $context): void
    {
        $this->context = $context;
    }
}
