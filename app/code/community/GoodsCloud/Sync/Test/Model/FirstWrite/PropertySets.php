<?php

class GoodsCloud_Sync_Test_Model_FirstWrite_PropertySets extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @loadFixture storesWithGcChannelId.yaml
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
                        $propertySetData = new Varien_Object();
                        $propertySetData->setData(
                            array(
                                'channel_id'          => 126,
                                'description'         => '',
                                'external_identifier' => $attributeSet->getId(),
                                'id'                  => mt_rand(),
                                'label'               => $attributeSet->getAttributeSetName(
                                ),
                            )
                        );

                        return $propertySetData;
                    }
                )
            );

        $stores = Mage::app()->getStores();

        $productEntityId = Mage::getModel('eav/entity_type')->loadByCode('catalog_product')->getId();
        /** @var $attributeSets Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection */
        $attributeSets = Mage::getResourceModel('eav/entity_attribute_set_collection')
            ->addFieldToFilter('entity_type_id', $productEntityId);


        $firstWritePropertySets = Mage::getModel('goodscloud_sync/firstWrite_propertySets');
        /** @var GoodsCloud_Sync_Model_Api $apiMock */
        $firstWritePropertySets->setApi($apiMock);
        $firstWritePropertySets->createPropertySetsFromAttributeSets($attributeSets, $stores);

        foreach ($attributeSets as $sets) {
            $this->assertJson($sets->getGcPropertySetIds());
            $this->assertNotEmpty($sets->getGcPropertySetIds());
        }
    }

    /**
     * @loadFixture              stores.yaml
     *
     * @expectedException Mage_Core_Exception
     * @expectedExceptionMessage Store Canada Store has no gc channel id set!
     */
    public function testCreatePropertySetWithoutChannelId()
    {
        $this->createAttributeSets();
        $apiMock = $this->getModelMock('goodscloud_sync/api', array('createPropertySet'), false, array(), '', false);

        $stores = Mage::app()->getStores();

        $productEntityId = Mage::getModel('eav/entity_type')->loadByCode('catalog_product')->getId();
        /** @var $attributeSets Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection */
        $attributeSets = Mage::getResourceModel('eav/entity_attribute_set_collection')
            ->addFieldToFilter('entity_type_id', $productEntityId);


        $firstWritePropertySets = Mage::getModel('goodscloud_sync/firstWrite_propertySets');
        /** @var GoodsCloud_Sync_Model_Api $apiMock */
        $firstWritePropertySets->setApi($apiMock);
        $firstWritePropertySets->createPropertySetsFromAttributeSets($attributeSets, $stores);
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
                'gc_property_set_ids' => '',
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

    protected function tearDown()
    {
        Mage::getSingleton('core/resource')->getConnection('core_write')->query(
            'UPDATE eav_attribute_set SET gc_property_set_ids = NULL;'
        );
    }


}
