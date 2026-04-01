<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\ProductSetStorage;

use Spryker\Shared\Kernel\AbstractBundleConfig;

class ProductSetStorageConfig extends AbstractBundleConfig
{
    /**
     * Specification:
     * - Queue name used for product set publish events.
     *
     * @api
     *
     * @var string
     */
    public const string PUBLISH_PRODUCT_SET_QUEUE = 'publish.product_set';
}
