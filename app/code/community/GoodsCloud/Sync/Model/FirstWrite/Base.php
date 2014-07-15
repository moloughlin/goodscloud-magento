<?php

class GoodsCloud_Sync_Model_FirstWrite_Base
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
     * get the api object
     *
     * @return GoodsCloud_Sync_Model_Api
     */
    protected function getApi()
    {
        return $this->api;
    }


}