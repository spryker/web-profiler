<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\WebProfiler\Loader;

use Spryker\Shared\Twig\Loader\FilesystemLoaderInterface;

interface FilesystemLoaderBuilderInterface
{
    public function createFilesystemLoader(): FilesystemLoaderInterface;
}
