<?php

class GoodsCloud_Sync_Test_Model_FirstWrite_PropertySchemas extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @loadFixture storesWithGcChannelId.yaml
     */
    public function testCreatePropertySet()
    {
        $apiMock = $this->getModelMock('goodscloud_sync/api', array('createPropertySchema'), false, array(), '', false);
        // we'll have default + three storeviews in the fixtures, so we write three storeviews
        $apiMock->expects($this->exactly(64)) // 16 attributes not ignored -> 16 * 4 storeviews = 64
        ->method('createPropertySchema')
            ->will(
                $this->returnCallback(
                    function (Mage_Eav_Model_Entity_Attribute $attribute) {
                        $propertySchemaData = new Varien_Object();
                        $propertySchemaData->setData(
                            array(
                                'channel_id'          => 126,
                                'description'         => '',
                                'external_identifier' => $attribute->getId(),
                                'id'                  => mt_rand(),
                                'label'               => $attribute->getName(),
                            )
                        );

                        return $propertySchemaData;
                    }
                )
            );

        $stores = Mage::app()->getStores();

        $ignoredAttributes = Mage::helper('goodscloud_sync/api')->getIgnoredAttributes();

        /** @var $attributes Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection */
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection');
        $attributes->addFieldToFilter('attribute_code', array('nin' => $ignoredAttributes));

        $firstWritePropertySchemas = Mage::getModel('goodscloud_sync/firstWrite_propertySchemas');
        /** @var GoodsCloud_Sync_Model_Api $apiMock */
        $firstWritePropertySchemas->setApi($apiMock);
        $firstWritePropertySchemas->createPropertySchemasFromAttributes($attributes, $stores);

        foreach ($attributes as $attribute) {
            $this->assertJson($attribute->getGcPropertySchemaIds());
            $this->assertNotEmpty($attribute->getGcPropertySchemaIds());
        }
    }

    /**
     * @loadFixture              stores.yaml
     *
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage Store Canada Store has no gc channel id set!
     */
    public function testCreatePropertySchemasWithoutChannelId()
    {
        $apiMock = $this->getModelMock('goodscloud_sync/api', array('createPropertySchema'), false, array(), '', false);

        $stores = Mage::app()->getStores();

        $ignoredAttributes = Mage::helper('goodscloud_sync/api')->getIgnoredAttributes();

        /** @var $attributes Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection */
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection');
        $attributes->addFieldToFilter('attribute_code', array('nin' => $ignoredAttributes));

        $firstWritePropertySchemas = Mage::getModel('goodscloud_sync/firstWrite_propertySchemas');
        /** @var GoodsCloud_Sync_Model_Api $apiMock */
        $firstWritePropertySchemas->setApi($apiMock);
        $firstWritePropertySchemas->createPropertySchemasFromAttributes($attributes, $stores);
    }

    protected function tearDown()
    {
        Mage::getSingleton('core/resource')->getConnection('core_write')->query(
            'UPDATE catalog_eav_attribute SET gc_property_schema_ids = NULL;'
        );
    }


}
