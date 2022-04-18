<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductSetStorage\Communication\Plugin\Event\Listener;

use Spryker\Zed\Event\Dependency\Plugin\EventBulkHandlerInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\ProductSet\Dependency\ProductSetEvents;

/**
 * @deprecated Use {@link \Spryker\Zed\ProductSetStorage\Communication\Plugin\Event\Listener\ProductSetStoragePublishListener}
 *   and {@link \Spryker\Zed\ProductSetStorage\Communication\Plugin\Event\Listener\ProductSetStorageUnpublishListener} instead.
 *
 * @method \Spryker\Zed\ProductSetStorage\Persistence\ProductSetStorageQueryContainerInterface getQueryContainer()
 * @method \Spryker\Zed\ProductSetStorage\Communication\ProductSetStorageCommunicationFactory getFactory()
 * @method \Spryker\Zed\ProductSetStorage\Business\ProductSetStorageFacadeInterface getFacade()
 * @method \Spryker\Zed\ProductSetStorage\ProductSetStorageConfig getConfig()
 */
class ProductSetStorageListener extends AbstractPlugin implements EventBulkHandlerInterface
{
    /**
     * @api
     *
     * @param array<\Generated\Shared\Transfer\EventEntityTransfer> $eventEntityTransfers
     * @param string $eventName
     *
     * @return void
     */
    public function handleBulk(array $eventEntityTransfers, $eventName)
    {
        $productSetIds = $this->getFactory()->getEventBehaviorFacade()->getEventTransferIds($eventEntityTransfers);

        if (
            $eventName === ProductSetEvents::ENTITY_SPY_PRODUCT_SET_DELETE ||
            $eventName === ProductSetEvents::ENTITY_SPY_PRODUCT_SET_DATA_DELETE ||
            $eventName === ProductSetEvents::PRODUCT_SET_UNPUBLISH
        ) {
            $this->getFacade()->unpublish($productSetIds);

            return;
        }

        $this->getFacade()->publish($productSetIds);
    }
}
