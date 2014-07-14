<?php

class GoodsCloud_Sync_Test_Model_Api extends EcomDev_PHPUnit_Test_Case
{
    private $model;

    protected function setUp()
    {
        $this->model = Mage::getModel('goodscloud_sync/api');
    }

    public function testGetApiModel()
    {
        $this->assertInstanceOf('GoodsCloud_Sync_Model_Api', $this->model);
    }

}