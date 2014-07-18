<?php

class GoodsCloud_Sync_Test_Model_FirstWrite extends EcomDev_PHPUnit_Test_Case
{
    public function testMagento2Goodscloud()
    {
        $firstWrite = Mage::getModel('goodscloud_sync/firstWrite');

        // write channels
        $modelMock = $this->mockModel('goodscloud_sync/firstWrite_channels', array('createChannelsFromStoreviews'));
        $modelMock->expects($this->once())
            ->method('createChannelsFromStoreviews');

        $this->replaceByMock('model', 'goodscloud_sync/firstWrite_channels', $modelMock);

        // write property sets
        $modelMock = $this->mockModel(
            'goodscloud_sync/firstWrite_propertySets', array('createPropertySetsFromAttributeSets')
        );
        $modelMock->expects($this->once())
            ->method('createPropertySetsFromAttributeSets');

        $this->replaceByMock('model', 'goodscloud_sync/firstWrite_propertySets', $modelMock);

        // write property schemas
        $modelMock = $this->mockModel(
            'goodscloud_sync/firstWrite_propertySchemas', array('createPropertySchemasFromAttributes')
        );
        $modelMock->expects($this->once())
            ->method('createPropertySchemasFromAttributes');

        $this->replaceByMock('model', 'goodscloud_sync/firstWrite_propertySchemas', $modelMock);

        // create category
        $modelMock = $this->mockModel(
            'goodscloud_sync/firstWrite_categories', array('createCategories')
        );
        $modelMock->expects($this->once())
            ->method('createCategories');

        $this->replaceByMock('model', 'goodscloud_sync/firstWrite_categories', $modelMock);


        $firstWrite->writeMagentoToGoodscloud();
    }
}
