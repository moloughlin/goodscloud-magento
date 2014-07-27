<?php

class GoodsCloud_Sync_Test_Model_FirstWrite_PropertySchema2PropertySetMapper extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @loadFixture storesWithGcChannelId.yaml
     */
    public function testMapPropertySchema2PropertySet()
    {

        $this->createAttributeSets();
        $apiMock = $this->getModelMock(
            'goodscloud_sync/api', array('mapPropertySchema2PropertySet'), false, array(), '', false
        );
        $apiMock->expects($this->exactly(2))
            ->method('mapPropertySchema2PropertySet')
            ->will(
                $this->returnValue(true)
            );

        $stores = Mage::app()->getStores();

        $productEntityId = Mage::getModel('eav/entity_type')->loadByCode('catalog_product')->getId();
        /** @var $attributeSets Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection */
        $attributeSets = Mage::getResourceModel('eav/entity_attribute_set_collection')
            ->addFieldToFilter('entity_type_id', $productEntityId);


        $firstWritePropertySets = Mage::getModel('goodscloud_sync/firstWrite_propertySchema2PropertySetMapper');
        /** @var GoodsCloud_Sync_Model_Api $apiMock */
        $firstWritePropertySets->setApi($apiMock);
        $firstWritePropertySets->mapProperty2PropertySets($attributeSets, $stores);
    }


    /**
     *  create a few attribute sets for testing
     */
    private function createAttributeSets()
    {
        $productEntityId = Mage::getModel('eav/entity_type')->loadByCode('catalog_product')->getId();
        /* @var $default Mage_Eav_Model_Entity_Attribute_Set */
        $default = Mage::getModel('eav/entity_attribute_set')->load(4);
        $default->addData(
            array(
                'entity_type_id'      => $productEntityId,
                'attribute_set_name'  => 'Default',
                'sort_order'          => '1',
                'gc_property_set_ids' => json_encode(array(1 => 1)),
            )
        );
        $default->save();

        /* @var $furniture Mage_Eav_Model_Entity_Attribute_Set */
        $furniture = Mage::getModel('eav/entity_attribute_set')->load('Furniture', 'attribute_set_name');
        $furniture->addData(
            array(
                'entity_type_id'      => $productEntityId,
                'attribute_set_name'  => 'Furniture',
                'sort_order'          => '1',
                'gc_property_set_ids' => json_encode(array(1 => 45)),
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
                'gc_property_set_ids' => json_encode(array(1 => 45, 2 => 12)),
            )
        );
        $shirt->save();
    }

    protected function tearDown()
    {
        Mage::getSingleton('core/resource')->getConnection('core_write')->query(
            'UPDATE eav_attribute_set SET gc_property_set_ids = NULL;'
        );
    }

}
