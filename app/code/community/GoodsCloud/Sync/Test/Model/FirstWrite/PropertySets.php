<?php

class GoodsCloud_Sync_Test_Model_FirstWrite_PropertySets extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @loadFixture stores.yaml
     */
    public function testCreatePropertySet()
    {
        $this->createAttributeSets();
        $apiMock = $this->getModelMock('goodscloud_sync/api', array('createPropertySet'), false, array(), '', false);
        // we'll have default + three storeviews in the fixtures, so we write three storeviews
        $apiMock->expects($this->exactly(12))
            ->method('createPropertySet')
            ->will(
                $this->returnCallback(
                    function ($attributeSet) {
                        $propertySetData = new stdClass();
                        $propertySetData->channel_id = 126;
                        $propertySetData->description = '';
                        $propertySetData->external_identifier = $attributeSet->getId();
                        $propertySetData->id = mt_rand();
                        $propertySetData->label = $attributeSet->getAttributeSetName();
                        return $propertySetData;
                    }
                )
            );

        $stores = Mage::app()->getStores();

        $ignoredAttributes = Mage::helper('goodscloud_sync/api')->getIgnoredAttributes();
        $productEntityId = Mage::getModel('eav/entity_type')->loadByCode('catalog_product')->getId();

        /** @var $attributeSets Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection */
        $attributeSets = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addFieldToFilter('attribute_code', array('nin' => $ignoredAttributes))
            ->addFieldToFilter('entity_type_id', $productEntityId);


        $firstWritePropertySets = Mage::getModel('goodscloud_sync/firstWrite_propertySets');
        /** @var GoodsCloud_Sync_Model_Api $apiMock */
        $firstWritePropertySets->setApi($apiMock);
        $firstWritePropertySets->createPropertySetsFromAttributeSets($attributeSets, $stores);

        foreach ($attributeSets as $sets) {
            $this->assertJson($sets->getGcPropertySetIds());
        }
    }

    /**
     *  create a few attribute sets for testing
     */
    private function createAttributeSets()
    {
        $productEntityId = Mage::getModel('eav/entity_type')->loadByCode('catalog_product')->getId();
        /* @var $furniture Mage_Eav_Model_Entity_Attribute_Set */
        $furniture = Mage::getModel('eav/entity_attribute_set')->load('Furniture', 'attribute_set_name');
        $furniture->addData(
            array(
                'entity_type_id'      => $productEntityId,
                'attribute_set_name'  => 'Furniture',
                'sort_order'          => '1',
                'gc_property_set_ids' => json_encode(array(23 => 45)),
            )
        );
        $furniture->save();

        /* @var $shirt Mage_Eav_Model_Entity_Attribute_Set */
        $shirt = Mage::getModel('eav/entity_attribute_set')->load('Shirt', 'attribute_set_name');
        $shirt->addData(
            array(
                'entity_type_id'      => $productEntityId,
                'attribute_set_name'  => 'Shirt',
                'sort_order'          => '1',
                'gc_property_set_ids' => '',
            )
        );
        $shirt->save();
    }
}
