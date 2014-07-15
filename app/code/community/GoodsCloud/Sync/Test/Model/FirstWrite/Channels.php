<?php

class GoodsCloud_Sync_Test_Model_FirstWrite_Channels extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @loadFixture
     */
    public function testChannelCreation()
    {
        $apiMock = $this->getModelMock('goodscloud_sync/api', array('createChannel'), false, array(), '', false);
        // we'll have default + three storeviews in the fixtures, so we write three storeviews
        $apiMock->expects($this->exactly(4))
            ->method('createChannel')
            ->will($this->returnValue(true));

        $firstWriteChannels = Mage::getModel('goodscloud_sync/firstWrite_channels');
        $firstWriteChannels->setApi($apiMock);
        $stores = Mage::app()->getStores();
        $firstWriteChannels->createChannelFromStoreviews($stores);
    }

    /**
     * @expectedException Mage_Core_Exception
     */
    public function testErrorWhileCreatingChannel()
    {
        $apiMock = $this->getModelMock('goodscloud_sync/api', array('createChannel'), false, array(), '', false);
        // we'll have default + three storeviews in the fixtures, so we write three storeviews
        $apiMock->expects($this->any())
            ->method('createChannel')
            ->will($this->returnValue(false));

        $firstWriteChannels = Mage::getModel('goodscloud_sync/firstWrite_channels');
        $firstWriteChannels->setApi($apiMock);
        $storeMock = Mage::getModel('core/store');
        $firstWriteChannels->createChannelFromStoreviews(array($storeMock));
    }
}