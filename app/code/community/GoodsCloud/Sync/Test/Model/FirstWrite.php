<?php

class GoodsCloud_Sync_Test_Model_FirstWrite extends EcomDev_PHPUnit_Test_Case
{

    /**
     * @loadFixture              configurationIdentifierType.yaml
     * @expectedException        GoodsCloud_Sync_Model_Exception_MissingConfigurationException
     * @expectedExceptionMessage Please configure identifier attribute.
     */
    public function testIdentifierAttributeConfigured()
    {
        $firstWrite = Mage::getModel('goodscloud_sync/firstWrite');
        $firstWrite->writeMagentoToGoodscloud();
    }

    /**
     * @loadFixture              configurationIdentifierAttribute.yaml
     * @expectedException        GoodsCloud_Sync_Model_Exception_MissingConfigurationException
     * @expectedExceptionMessage Please configure identifier type.
     */
    public function testIdentifierTypeConfigured()
    {
        $firstWrite = Mage::getModel('goodscloud_sync/firstWrite');
        $firstWrite->writeMagentoToGoodscloud();
    }

    /**
     * @loadFixture configurationSettings.yaml
     */
    public function testMagento2Goodscloud()
    {
        $firstWrite = Mage::getModel('goodscloud_sync/firstWrite');

        // write channels
        $modelMock = $this->mockModel(
            'goodscloud_sync/firstWrite_channels',
            array('createChannelsFromStoreviews')
        );
        $modelMock->expects($this->once())
            ->method('createChannelsFromStoreviews');

        $this->replaceByMock(
            'model', 'goodscloud_sync/firstWrite_channels', $modelMock
        );

        // write property sets
        $modelMock = $this->mockModel(
            'goodscloud_sync/firstWrite_propertySets',
            array('createPropertySetsFromAttributeSets')
        );
        $modelMock->expects($this->once())
            ->method('createPropertySetsFromAttributeSets');

        $this->replaceByMock(
            'model', 'goodscloud_sync/firstWrite_propertySets', $modelMock
        );

        // write property schemas
        $modelMock = $this->mockModel(
            'goodscloud_sync/firstWrite_propertySchemas',
            array('createPropertySchemasFromAttributes')
        );
        $modelMock->expects($this->once())
            ->method('createPropertySchemasFromAttributes');

        $this->replaceByMock(
            'model', 'goodscloud_sync/firstWrite_propertySchemas', $modelMock
        );

        // create category
        $modelMock = $this->mockModel(
            'goodscloud_sync/firstWrite_categories', array('createCategories')
        );

        $modelMock->expects($this->once())
            ->method('createCategories');

        $this->replaceByMock(
            'model', 'goodscloud_sync/firstWrite_categories', $modelMock
        );

        // replace not used api due to bad architecture
        $modelMock = $this->getModelMock(
            'goodscloud_sync/api',
            array(),
            false,
            array(),
            '',
            false // don't call constructor
        );
        $this->replaceByMock(
            'model', 'goodscloud_sync/api', $modelMock
        );

        $firstWrite->writeMagentoToGoodscloud();
    }
}
