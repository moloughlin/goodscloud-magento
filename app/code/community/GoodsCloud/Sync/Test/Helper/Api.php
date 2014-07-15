<?php

/**
 * Class GoodsCloud_Sync_Test_Helper_Api
 */
class GoodsCloud_Sync_Test_Helper_Api extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @loadFixture
     */
    public function testSettings()
    {
        $helper = Mage::helper('goodscloud_sync/api');
        $this->assertEquals('http://sandbox.goodscloud.com', $helper->getUri());
        $this->assertEquals('api@goodscloud.com', $helper->getEmail());
        $this->assertEquals('!"§g$&6%ZHTRasdB', $helper->getPassword());
    }

    public function testGetIgnoredAttributes()
    {
        $helper = Mage::helper('goodscloud_sync/api');
        $this->assertContains('sku', $helper->getIgnoredAttributes());
    }

    public function testGetBooleanSourceModels()
    {
        $helper = Mage::helper('goodscloud_sync/api');
        $this->assertContains('eav/entity_attribute_source_boolean', $helper->getBooleanSourceModels());
    }

    public function testGetEnumTypes()
    {
        $helper = Mage::helper('goodscloud_sync/api');
        $this->assertContains('select', $helper->getEnumTypes());

    }

    /**
     * @dataProvider dataProvider
     *
     * @param string $frontendInput
     * @param string $sourceModel
     * @param string $backendType
     */
    public function testGetPropertySchemaType($frontendInput, $sourceModel, $backendType)
    {
        $helper = Mage::helper('goodscloud_sync/api');

        $attribute = Mage::getModel('eav/entity_attribute')
            ->setData(
                array(
                    'frontend_input' => $frontendInput,
                    'source_model'   => $sourceModel,
                    'backend_type'   => $backendType
                )
            );

        $expectationKey = str_replace('/', '_', "C$frontendInput-$sourceModel-$backendType");

        $this->assertEquals(
            $this->expected($expectationKey)->getType(),
            $helper->getPropertySchemaTypeForAttribute($attribute)
        );
    }
}
