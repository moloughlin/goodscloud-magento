<?php

class GoodsCloud_Sync_Test_Model_Api extends EcomDev_PHPUnit_Test_Case
{
    /**
     *  test that api is fetched from factory
     */
    public function testSetApiModel()
    {
        $factoryMock = $this->getModelMock('goodscloud_sync/api_factory', array('getApi'));

        $factoryMock->expects($this->once())
            ->method('getApi');

        $this->replaceByMock('model', 'goodscloud_sync/api_factory', $factoryMock);

        Mage::getModel('goodscloud_sync/api');
    }
}