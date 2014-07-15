<?php

class GoodsCloud_Sync_Test_Model_FirstWrite_Channels extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @loadFixture stores.yaml
     */
    public function testChannelCreation()
    {
        $apiMock = $this->getModelMock('goodscloud_sync/api', array('createChannel'), false, array(), '', false);
        // we'll have default + three storeviews in the fixtures, so we write three storeviews
        $apiMock->expects($this->exactly(3))
            ->method('createChannel')
            ->will(
                $this->returnCallback(
                    function ($view) {
                        $channelData = new stdClass();
                        // external identifier is not needed for the test (yet)
                        $channelData->external_identifier = $view->getId();
                        $channelData->id = mt_rand();
                        return $channelData;
                    }
                )
            );

        $stores = Mage::app()->getStores();

        $firstWriteChannels = Mage::getModel('goodscloud_sync/firstWrite_channels');
        $firstWriteChannels->setApi($apiMock);
        $firstWriteChannels->createChannelFromStoreviews($stores);

        foreach ($stores as $store) {
            $this->assertNotNull($store->getGcId());
        }
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