<?php

class GoodsCloud_Sync_Model_FirstWrite
{
    /**
     * do all the things which are needed, when magento and goodscloud are the first time connected
     */
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