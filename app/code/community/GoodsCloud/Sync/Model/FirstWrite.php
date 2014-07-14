<?php

class GoodsCloud_Sync_Model_FirstWrite
{
    public function writeMagentoToGoodscloud()
    {
        // Add a Channel for every StoreView
        $this->createChannelsFromStoreView();

        // Add every AttributeSet as PropertySet to every Channel
        $this->createPropertySetsFromAttributeSets();

        // Add every Attribute as PropertySchema to every PropertySet
        $this->createPropertySchemasFromAttributes();

        // Copy the category tree to GoodsCloud
        $this->createGCCategoriesFromCategories();
    }

    private function createChannelsFromStoreView()
    {
        $stores = Mage::app()->getStores();

        return Mage::getModel('goodscloud_sync/firstWrite_channels')
            ->createChannelFromStoreviews($stores);
    }

    private function createPropertySetsFromAttributeSets()
    {
    }

    private function createPropertySchemasFromAttributes()
    {
    }

    private function createGCCategoriesFromCategories()
    {
    }
}