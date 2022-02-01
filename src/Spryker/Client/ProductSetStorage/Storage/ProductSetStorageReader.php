<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\ProductSetStorage\Storage;

use Generated\Shared\Transfer\SynchronizationDataTransfer;
use Spryker\Client\Kernel\Locator;
use Spryker\Client\ProductSetStorage\Dependency\Client\ProductSetStorageToStorageClientInterface;
use Spryker\Client\ProductSetStorage\Dependency\Service\ProductSetStorageToSynchronizationServiceInterface;
use Spryker\Client\ProductSetStorage\Mapper\ProductSetStorageMapperInterface;
use Spryker\Client\ProductSetStorage\ProductSetStorageConfig;
use Spryker\Shared\ProductSetStorage\ProductSetStorageConstants;

class ProductSetStorageReader implements ProductSetStorageReaderInterface
{
    /**
     * @var \Spryker\Client\ProductSetStorage\Dependency\Client\ProductSetStorageToStorageClientInterface
     */
    protected $storageClient;

    /**
     * @var \Spryker\Client\ProductSetStorage\Dependency\Service\ProductSetStorageToSynchronizationServiceInterface
     */
    protected $synchronizationService;

    /**
     * @var \Spryker\Client\ProductSetStorage\Mapper\ProductSetStorageMapperInterface
     */
    protected $productSetStorageMapper;

    /**
     * @param \Spryker\Client\ProductSetStorage\Dependency\Client\ProductSetStorageToStorageClientInterface $storageClient
     * @param \Spryker\Client\ProductSetStorage\Dependency\Service\ProductSetStorageToSynchronizationServiceInterface $synchronizationService
     * @param \Spryker\Client\ProductSetStorage\Mapper\ProductSetStorageMapperInterface $productSetStorageMapper
     */
    public function __construct(
        ProductSetStorageToStorageClientInterface $storageClient,
        ProductSetStorageToSynchronizationServiceInterface $synchronizationService,
        ProductSetStorageMapperInterface $productSetStorageMapper
    ) {
        $this->storageClient = $storageClient;
        $this->synchronizationService = $synchronizationService;
        $this->productSetStorageMapper = $productSetStorageMapper;
    }

    /**
     * @param int $idProductSet
     * @param string $localeName
     *
     * @return \Generated\Shared\Transfer\ProductSetDataStorageTransfer|null
     */
    public function getProductSetByIdProductSet($idProductSet, $localeName)
    {
        $productSetStorageStorageData = $this->getStorageData((int)$idProductSet, $localeName);

        if (!$productSetStorageStorageData) {
            return null;
        }

        return $this->productSetStorageMapper->mapDataToTransfer($productSetStorageStorageData);
    }

    /**
     * @param int $idProductSet
     * @param string $localeName
     *
     * @return array|null
     */
    protected function getStorageData(int $idProductSet, string $localeName): ?array
    {
        if (ProductSetStorageConfig::isCollectorCompatibilityMode()) {
            $clientLocatorClass = Locator::class;
            /** @var \Generated\Zed\Ide\AutoCompletion&\Spryker\Shared\Kernel\LocatorLocatorInterface $locator */
            $locator = $clientLocatorClass::getInstance();
            $productSetClient = $locator->productSet()->client();
            $collectorData = $productSetClient->findProductSetByIdProductSet($idProductSet);

            $collectorData = $collectorData->toArray();
            $collectorData['product_abstract_ids'] = $collectorData['id_product_abstracts'];
            unset($collectorData['id_product_abstracts'], $collectorData['images']);

            $imageSets = [];
            foreach ($collectorData['image_sets'] as $imageSetName => $images) {
                $imageSets[] = [
                    'name' => $imageSetName,
                    'images' => $images,
                ];
            }

            $collectorData['image_sets'] = $imageSets;

            return $collectorData;
        }
        $synchronizationDataTransfer = new SynchronizationDataTransfer();
        $synchronizationDataTransfer
            ->setReference($idProductSet)
            ->setLocale($localeName);

        $key = $this->synchronizationService
            ->getStorageKeyBuilder(ProductSetStorageConstants::PRODUCT_SET_RESOURCE_NAME)
            ->generateKey($synchronizationDataTransfer);

        $productSet = $this->storageClient->get($key);

        return $productSet;
    }
}
