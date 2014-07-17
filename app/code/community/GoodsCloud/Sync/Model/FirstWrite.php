<?php

class GoodsCloud_Sync_Model_FirstWrite
{
    /**
     * @var GoodsCloud_Sync_Model_Api
     */
    private $api;

    /**
     * do all the things which are needed, when magento and goodscloud are the first time connected
     */
    public function writeMagentoToGoodscloud()
    {
        $this->api = Mage::getModel('goodscloud_sync/api');
        // Add a Channel for every StoreView
        $this->createChannelsFromStoreView();

        // Add every AttributeSet as PropertySet to every Channel
        $this->createPropertySetsFromAttributeSets();

        // Add every Attribute as PropertySchema to every PropertySet
        $this->createPropertySchemasFromAttributes();

        // Copy the category tree to GoodsCloud
        $this->createGCCategoriesFromCategories();
    }

    /**
     * create all channels in goodscloud from storeview data
     *
     * @return bool
     */
    private function createChannelsFromStoreView()
    {
        /* @var $stores Mage_Core_Model_Store[] */
        $stores = Mage::app()->getStores();

        Mage::getModel('goodscloud_sync/firstWrite_channels')
            ->setApi($this->api)
            ->createChannelsFromStoreviews($stores);
    }

    private function createPropertySetsFromAttributeSets()
    {
        $stores = Mage::app()->getStores();
        $productEntityId = Mage::getModel('eav/entity_type')->loadByCode('catalog_product')->getId();
        /* @var $attributeSets Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection */
        $attributeSets = Mage::getResourceModel('eav/entity_attribute_set_collection')
            ->addFieldToFilter('entity_type_id', $productEntityId);

        Mage::getModel('goodscloud_sync/firstWrite_propertySets')
            ->setApi($this->api)
            ->createPropertySetsFromAttributeSets($attributeSets, $stores);
    }

    private function createPropertySchemasFromAttributes()
    {
        $productEntityType = Mage::getModel('eav/entity_type')->loadByCode('catalog_product');

        /** @var $attributes Mage_Eav_Model_Resource_Entity_Attribute_Collection */
        $attributes = Mage::getResourceModel('eav/entity_attribute_collection');
        $attributes->setEntityTypeFilter($productEntityType);

        $stores = Mage::app()->getStores();

        Mage::getModel('goodscloud_sync/firstWrite_propertySchemas')
            ->setApi($this->api)
            ->createPropertySchemasFromAttributes($attributes, $stores);
    }

    }

    private function createGCCategoriesFromCategories()
    {
    }
}
