<?php

class GoodsCloud_Sync_Model_Api
{
    /**
     * @var Goodscloud
     */
    private $api;

    /**
     * get the api object from the factory
     */
    public function __construct()
    {
        $factory = Mage::getModel('goodscloud_sync/api_factory');
        $this->api = $factory->getApi();
    }

    public function getChannels()
    {
        return $this->get('channel');
    }

    private function get($model)
    {
        $response = $this->api->get("/api/internal/$model");
        $collection = Mage::getModel('goodscloud_sync/api_' . $model . '_collection');
        foreach ($response->objects as $objects) {
            $collection->addItem(Mage::getModel('goodscloud_sync/api_' . $model)->setData(get_object_vars($objects)));
        }

        return $collection;
    }
}
