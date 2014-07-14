<?php

class GoodsCloud_Sync_Model_FirstWrite_Channels
{
    /**
     * @var GoodsCloud_Sync_Model_Api
     */
    private $api;

    /**
     * @param GoodsCloud_Sync_Model_Api $api
     */
    public function setApi(GoodsCloud_Sync_Model_Api $api)
    {
        $this->api = $api;
    }

    /**
     * create channels from store views
     *
     * @param Mage_Core_Model_Store[] $stores
     *
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function createChannelFromStoreviews(array $stores)
    {
        foreach ($stores as $view) {
            if (!$this->createChannelFromStoreview($view)) {
                // todo do it transactional against goodscloud?
                Mage::throwException('Error while creating channels');
            }
        }

        return true;
    }

    private function createChannelFromStoreview($view)
    {
        return $this->api->createChannel($view);
    }


}