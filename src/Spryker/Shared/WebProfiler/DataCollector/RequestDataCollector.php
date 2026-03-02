<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\WebProfiler\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector as SymfonyRequestDataCollector;

/**
 * @deprecated Will be removed without replacement.
 */
class RequestDataCollector extends SymfonyRequestDataCollector
{
    public function getMethod(): string
    {
        return $this->data['method'] ?? 'undefined';
    }
}
