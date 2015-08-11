<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace Functional\SprykerFeature\Zed\Product;

use Codeception\TestCase\Test;
use Generated\Shared\Transfer\AbstractProductTransfer;
use Generated\Shared\Transfer\ConcreteProductTransfer;
use Generated\Shared\Transfer\LocalizedAttributesTransfer;
use Generated\Zed\Ide\AutoCompletion;
use SprykerEngine\Zed\Kernel\Locator;
use SprykerEngine\Zed\Kernel\Persistence\Factory as PersistenceFactory;
use SprykerFeature\Zed\Product\Business\ProductFacade;
use SprykerFeature\Zed\Product\Persistence\ProductQueryContainer;
use SprykerEngine\Zed\Locale\Business\LocaleFacade;
use SprykerFeature\Zed\Product\Persistence\Propel\SpyAbstractProduct;
use SprykerFeature\Zed\Product\Persistence\Propel\SpyLocalizedProductAttributes;
use SprykerFeature\Zed\Product\Persistence\Propel\SpyProduct;
use SprykerFeature\Zed\Tax\Persistence\Propel\SpyTaxRate;
use SprykerFeature\Zed\Tax\Persistence\Propel\SpyTaxSet;
use SprykerFeature\Zed\Url\Business\UrlFacade;

class ProductFacadeTest extends Test
{

    const SKU_ABSTRACT_PRODUCT = 'Abstract product sku';

    const SKU_CONCRETE_PRODUCT = 'Concrete product sku';

    const TAX_SET_NAME = 'Sales Tax';

    const TAX_RATE_NAME = 'VAT';

    const TAX_RATE_PERCENTAGE = 10;

    const CONCRETE_PRODUCT_NAME = 'Concrete product name';

    /**
     * @var ProductFacade
     */
    protected $productFacade;

    /**
     * @var LocaleFacade
     */
    protected $localeFacade;

    /**
     * @var UrlFacade
     */
    protected $urlFacade;

    /**
     * @var ProductQueryContainer
     */
    protected $productQueryContainer;

    /**
     * @var AutoCompletion
     */
    protected $locator;

    protected function setUp()
    {
        parent::setUp();
        $this->locator = Locator::getInstance();

        $this->localeFacade = $this->locator->locale()->facade();
        $this->productFacade = $this->locator->product()->facade();
        $this->urlFacade = $this->locator->url()->facade();
        $this->productQueryContainer = new ProductQueryContainer(new PersistenceFactory('Product'), $this->locator);
    }

    /**
     * @group Product
     */
    public function testCreateAttributeTypeCreatesAndReturnsId()
    {
        $attributeTypeQuery = $this->productQueryContainer->queryAttributeTypeByName('AnAttributeType');
        $this->assertEquals(0, $attributeTypeQuery->count());

        $idAttributeType = $this->productFacade->createAttributeType('AnAttributeType', 'input');

        $this->assertEquals(1, $attributeTypeQuery->count());
        $this->assertEquals($idAttributeType, $attributeTypeQuery->findOne()->getIdType());
    }

    /**
     * @group Product
     */
    public function testCreateAttributeCreatesAndReturnsId()
    {
        $this->productFacade->createAttributeType('AnAttributeType', 'input');
        $attributeQuery = $this->productQueryContainer->queryAttributeByName('ANonExistentAttribute');
        $this->assertEquals(0, $attributeQuery->count());

        $idAttribute = $this->productFacade->createAttribute('ANonExistentAttribute', 'AnAttributeType');

        $this->assertEquals(1, $attributeQuery->count());
        $this->assertEquals($idAttribute, $attributeQuery->findOne()->getIdAttributesMetadata());
    }

    /**
     * @group Product
     */
    public function testHasAttributeTypeReturnsRightValue()
    {
        $this->assertFalse($this->productFacade->hasAttributeType('AnAttributeType'));
        $this->productFacade->createAttributeType('AnAttributeType', 'input');
        $this->assertTrue($this->productFacade->hasAttributeType('AnAttributeType'));
    }

    /**
     * @group Product
     */
    public function testHasAttributeReturnsRightValue()
    {
        $this->assertFalse($this->productFacade->hasAttribute('AnAttribute'));

        $this->productFacade->createAttributeType('AnAttributeType', 'input');
        $this->productFacade->createAttribute('AnAttribute', 'AnAttributeType');

        $this->assertTrue($this->productFacade->hasAttribute('AnAttribute'));
    }

    /**
     * @group Product
     */
    public function testCreateAbstractProductCreatesAndReturnsId()
    {
        $abstractProductQuery = $this->productQueryContainer->queryAbstractProductBySku('AnAbstractProductSku');

        $this->assertEquals(0, $abstractProductQuery->count());

        $abstractProduct = new AbstractProductTransfer();
        $abstractProduct->setSku('AnAbstractProductSku');
        $abstractProduct->setAttributes([]);
        $abstractProduct->setLocalizedAttributes(new LocalizedAttributesTransfer());

        $idAbstractProduct = $this->productFacade->createAbstractProduct($abstractProduct);

        $this->assertEquals(1, $abstractProductQuery->count());
        $this->assertEquals($idAbstractProduct, $abstractProductQuery->findOne()->getIdAbstractProduct());
    }

    /**
     * @group Product
     */
    public function testGetEffectiveTaxRateReturnsFloat()
    {
        $concreteProductQuery = $this->productQueryContainer->queryConcreteProductBySku('AConcreteProductSku');

        $this->assertEquals(0, $concreteProductQuery->count());

        $abstractProduct = new AbstractProductTransfer();
        $abstractProduct->setSku('AnAbstractProductSku');
        $abstractProduct->setAttributes([]);
        $abstractProduct->setLocalizedAttributes(new LocalizedAttributesTransfer());

        $idAbstractProduct = $this->productFacade->createAbstractProduct($abstractProduct);

        $concreteProduct = new ConcreteProductTransfer();
        $concreteProduct->setSku('AConcreteProductSku');
        $concreteProduct->setAttributes([]);
        $concreteProduct->setLocalizedAttributes(new LocalizedAttributesTransfer());
        $concreteProduct->setIsActive(true);

        $this->productFacade->createConcreteProduct($concreteProduct, $idAbstractProduct);

        $effectiveTaxRate = $this->productFacade->getEffectiveTaxRateForConcreteProduct('AConcreteProductSku');

        $this->assertInternalType('float', $effectiveTaxRate);
    }

    /**
     * @group Product
     */
    public function testHasAbstractProductReturnsRightValue()
    {
        $this->assertFalse($this->productFacade->hasAbstractProduct('AProductSku'));

        $abstractProduct = new AbstractProductTransfer();
        $abstractProduct->setSku('AProductSku');
        $abstractProduct->setAttributes([]);
        $abstractProduct->setLocalizedAttributes(new LocalizedAttributesTransfer());

        $this->productFacade->createAbstractProduct($abstractProduct);

        $this->assertTrue($this->productFacade->hasAbstractProduct('AProductSku'));
    }

    /**
     * @group Product
     */
    public function testCreateConcreteProductCreatesAndReturnsId()
    {
        $concreteProductQuery = $this->productQueryContainer->queryConcreteProductBySku('AConcreteProductSku');

        $this->assertEquals(0, $concreteProductQuery->count());

        $abstractProduct = new AbstractProductTransfer();
        $abstractProduct->setSku('AnAbstractProductSku');
        $abstractProduct->setAttributes([]);
        $abstractProduct->setLocalizedAttributes(new LocalizedAttributesTransfer());
        $idAbstractProduct = $this->productFacade->createAbstractProduct($abstractProduct);

        $concreteProduct = new ConcreteProductTransfer();
        $concreteProduct->setSku('AConcreteProductSku');
        $concreteProduct->setAttributes([]);
        $concreteProduct->setLocalizedAttributes(new LocalizedAttributesTransfer());
        $concreteProduct->setIsActive(true);
        $idConcreteProduct = $this->productFacade->createConcreteProduct($concreteProduct, $idAbstractProduct);

        $this->assertEquals(1, $concreteProductQuery->count());
        $this->assertEquals($idConcreteProduct, $concreteProductQuery->findOne()->getIdProduct());
    }

    /**
     * @group Product
     */
    public function testHasConcreteProductReturnsRightValue()
    {
        $this->assertFalse($this->productFacade->hasConcreteProduct('AConcreteProductSku'));

        $abstractProduct = new AbstractProductTransfer();
        $abstractProduct->setSku('AnAbstractProductSku');
        $abstractProduct->setAttributes([]);
        $abstractProduct->setLocalizedAttributes(new LocalizedAttributesTransfer());

        $idAbstractProduct = $this->productFacade->createAbstractProduct($abstractProduct);

        $concreteProduct = new ConcreteProductTransfer();
        $concreteProduct->setSku('AConcreteProductSku');
        $concreteProduct->setAttributes([]);
        $concreteProduct->setLocalizedAttributes(new LocalizedAttributesTransfer());
        $concreteProduct->setIsActive(true);
        $this->productFacade->createConcreteProduct($concreteProduct, $idAbstractProduct);

        $this->assertTrue($this->productFacade->hasConcreteProduct('AConcreteProductSku'));
    }

    /**
     * @group Product
     */
    public function testCreateProductUrlCreatesAndReturnsCorrectUrl()
    {
        $urlString = '/someUrl';
        $locale = $this->localeFacade->createLocale('ABCDE');

        $abstractProduct = new AbstractProductTransfer();
        $abstractProduct->setSku('AnAbstractProductSku');
        $abstractProduct->setAttributes([]);
        $abstractProduct->setLocalizedAttributes(new LocalizedAttributesTransfer());
        $idAbstractProduct = $this->productFacade->createAbstractProduct($abstractProduct);
        $url = $this->productFacade->createProductUrl('AnAbstractProductSku', $urlString, $locale);

        $this->assertTrue($this->urlFacade->hasUrl($urlString));

        $this->assertEquals($urlString, $url->getUrl());
        $this->assertEquals($idAbstractProduct, $url->getFkAbstractProduct());
        $this->assertEquals($idAbstractProduct, $url->getResourceId());
        $this->assertEquals('abstract_product', $url->getResourceType());
        $this->assertEquals($locale->getIdLocale(), $url->getFkLocale());
    }

    /**
     * @group Product
     */
    public function testGetAbstractSkuFromConcreteProduct()
    {
        $this->assertFalse($this->productFacade->hasConcreteProduct(self::SKU_CONCRETE_PRODUCT));

        $abstractProduct = new AbstractProductTransfer();
        $abstractProduct->setSku(self::SKU_ABSTRACT_PRODUCT);
        $abstractProduct->setAttributes([]);
        $abstractProduct->setLocalizedAttributes(new LocalizedAttributesTransfer());

        $idAbstractProduct = $this->productFacade->createAbstractProduct($abstractProduct);

        $concreteProduct = new ConcreteProductTransfer();
        $concreteProduct->setSku(self::SKU_CONCRETE_PRODUCT);
        $concreteProduct->setAttributes([]);
        $concreteProduct->setLocalizedAttributes(new LocalizedAttributesTransfer());
        $concreteProduct->setIsActive(true);
        $this->productFacade->createConcreteProduct($concreteProduct, $idAbstractProduct);

        $this->assertTrue($this->productFacade->hasConcreteProduct(self::SKU_CONCRETE_PRODUCT));

        $this->assertEquals($this->productFacade->getAbstractSkuFromConcreteProduct(self::SKU_CONCRETE_PRODUCT), self::SKU_ABSTRACT_PRODUCT);
    }

    /**
     * @group Product
     */
    public function testGetConcreteProduct()
    {
        $localeName = \SprykerEngine\Shared\Kernel\Store::getInstance()->getCurrentLocale();
        $localeTransfer = $this->localeFacade->getLocale($localeName);

        $taxRateEntity = new SpyTaxRate();
        $taxRateEntity->setRate(self::TAX_RATE_PERCENTAGE)
            ->setName(self::TAX_RATE_NAME);

        $taxSetEntity = new SpyTaxSet();
        $taxSetEntity->addSpyTaxRate($taxRateEntity)
            ->setName(self::TAX_SET_NAME);

        $abstractProductEntity = new SpyAbstractProduct();
        $abstractProductEntity->setSpyTaxSet($taxSetEntity)
            ->setAttributes('')
            ->setSku(self::SKU_ABSTRACT_PRODUCT);

        $localizedAttributesEntity = new SpyLocalizedProductAttributes();
        $localizedAttributesEntity->setName(self::CONCRETE_PRODUCT_NAME)
            ->setAttributes('')
            ->setFkLocale($localeTransfer->getIdLocale());

        $concreteProductEntity = new SpyProduct();
        $concreteProductEntity->setSpyAbstractProduct($abstractProductEntity)
            ->setAttributes('')
            ->addSpyLocalizedProductAttributes($localizedAttributesEntity)
            ->setSku(self::SKU_CONCRETE_PRODUCT)
            ->save();

        $concreteProductTransfer = $this->productFacade->getConcreteProduct($concreteProductEntity->getSku());
        $this->assertEquals(self::CONCRETE_PRODUCT_NAME, $concreteProductTransfer->getName());
        $this->assertEquals(self::SKU_CONCRETE_PRODUCT, $concreteProductTransfer->getSku());
        $this->assertEquals(self::SKU_ABSTRACT_PRODUCT, $concreteProductTransfer->getAbstractProductSku());
        $this->assertEquals($concreteProductEntity->getIdProduct(), $concreteProductTransfer->getIdConcreteProduct());
        $this->assertEquals($abstractProductEntity->getIdAbstractProduct(), $concreteProductTransfer->getIdAbstractProduct());

        $taxSetTransfer = $concreteProductTransfer->getTaxSet();
        $this->assertEquals(self::TAX_SET_NAME, $taxSetTransfer->getName());

        $this->assertNotEmpty($taxSetTransfer->getTaxRates());
        $taxRateTransfer = $taxSetTransfer->getTaxRates()[0];
        $this->assertEquals(self::TAX_RATE_NAME, $taxRateTransfer->getName());
        $this->assertEquals(self::TAX_RATE_PERCENTAGE, $taxRateTransfer->getRate());
    }

}
