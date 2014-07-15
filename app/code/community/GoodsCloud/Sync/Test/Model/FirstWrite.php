<?php

class GoodsCloud_Sync_Test_Model_FirstWrite extends EcomDev_PHPUnit_Test_Case
{
    public function testMagento2Goodscloud()
    {
        $firstWrite = Mage::getModel('goodscloud_sync/firstWrite');

        $modelMock = $this->mockModel('goodscloud_sync/firstWrite_channels', array('createChannelFromStoreviews'));
        $modelMock->expects($this->once())
            ->method('createChannelFromStoreviews');

        $this->replaceByMock('model', 'goodscloud_sync/firstWrite_channels', $modelMock);

        $firstWrite->writeMagentoToGoodscloud();
    }
}