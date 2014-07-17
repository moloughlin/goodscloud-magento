<?php

class GoodsCloud_Sync_Test_Model_FirstWrite_PropertySchemas extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @loadFixture stores.yaml
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
                        $propertySchemaData = new stdClass();
                        $propertySchemaData->channel_id = 126;
                        $propertySchemaData->description = '';
                        $propertySchemaData->external_identifier = $attribute->getId();
                        $propertySchemaData->id = mt_rand();
                        $propertySchemaData->label = $attribute->getName();
                        return $propertySchemaData;
                    }
                )
            );

        $stores = Mage::app()->getStores();

        /** @var $attributeSets Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection */
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection');

        $firstWritePropertySchemas = Mage::getModel('goodscloud_sync/firstWrite_propertySchemas');
        /** @var GoodsCloud_Sync_Model_Api $apiMock */
        $firstWritePropertySchemas->setApi($apiMock);
        $firstWritePropertySchemas->createPropertySchemasFromAttributes($attributes, $stores);

//        foreach ($attributeSets as $sets) {
//            $this->assertJson($sets->getGcPropertySetIds());
//        }
    }
}
