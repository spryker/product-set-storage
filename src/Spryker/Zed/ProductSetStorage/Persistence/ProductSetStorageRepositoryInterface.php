<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace Spryker\Zed\ProductSetStorage\Persistence;

use Generator;

interface ProductSetStorageRepositoryInterface
{
    /**
     * @deprecated Use {@link \Spryker\Zed\ProductSetStorage\Persistence\ProductSetStorageRepositoryInterface::getSitemapGeneratorUrls()} instead.
     *
     * @param string $storeName
     *
     * @return array<\Generated\Shared\Transfer\SitemapUrlTransfer>
     */
    public function getSitemapUrls(string $storeName): array;

    public function getSitemapGeneratorUrls(string $storeName, int $limit): Generator;
}
