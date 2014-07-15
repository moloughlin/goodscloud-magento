<?php

class GoodsCloud_Sync_Model_FirstWrite_Channels extends GoodsCloud_Sync_Model_FirstWrite_Base
{
    /**
     * create channels from store views
     *
     * @param Mage_Core_Model_Store[] $stores
     *
     * @return void
     * @throws Mage_Core_Exception
     */
    public function createChannelFromStoreviews(array $stores)
    {
        foreach ($stores as $view) {
            if (!$view->getGcId()) {
                $channelData = $this->createChannelFromStoreview($view);
                if (!$channelData) {
                    Mage::throwException('Error while creating channels');
                }
                $view->setGcId($channelData->id);
                $view->save();
            }
        }
    }

    /**
     * create a channel in goodscloud from storeview data
     *
     * @param Mage_Core_Model_Store $view
     *
     * @return bool|string|void
     */
    private function createChannelFromStoreview(Mage_Core_Model_Store $view)
    {
        return $this->getApi()->createChannel($view);
    }
}