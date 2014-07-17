<?php

class GoodsCloud_Sync_Test_Model_FirstWrite extends EcomDev_PHPUnit_Test_Case
{
    public function testMagento2Goodscloud()
    {
        $firstWrite = Mage::getModel('goodscloud_sync/firstWrite');

        $modelMock = $this->mockModel('goodscloud_sync/firstWrite_channels', array('createChannelsFromStoreviews'));
        $modelMock->expects($this->once())
            ->method('createChannelsFromStoreviews');

        $this->replaceByMock('model', 'goodscloud_sync/firstWrite_channels', $modelMock);

        $modelMock = $this->mockModel(
            'goodscloud_sync/firstWrite_propertySets', array('createPropertySetsFromAttributeSets')
        );
        $modelMock->expects($this->once())
            ->method('createPropertySetsFromAttributeSets');

        $this->replaceByMock('model', 'goodscloud_sync/firstWrite_propertySets', $modelMock);

        $modelMock = $this->mockModel(
            'goodscloud_sync/firstWrite_propertySchemas', array('createPropertySchemasFromAttributes')
        );
        $modelMock->expects($this->once())
            ->method('createPropertySchemasFromAttributes');

        $this->replaceByMock('model', 'goodscloud_sync/firstWrite_propertySchemas', $modelMock);


        $firstWrite->writeMagentoToGoodscloud();
    }
}
