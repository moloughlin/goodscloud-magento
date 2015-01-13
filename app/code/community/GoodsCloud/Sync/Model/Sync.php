<?php

class GoodsCloud_Sync_Model_Sync
{
    /**
     * @var GoodsCloud_Sync_Model_Api
     */
    private $api;

    /**
     *
     */
    public function syncWithGoodscloud()
    {
        $products = Mage::getModel('goodscloud_sync/sync_products');
        $products->setApi($this->getApi());
        $products->updateProductsByTimestamp();
    }

    /**
     * @param GoodsCloud_Sync_Model_Api $api
     */
    public function setApi($api)
    {
        $this->api = $api;
    }

    /**
     * @return GoodsCloud_Sync_Model_Api
     */
    private function getApi()
    {
        return $this->api;
    }
}
