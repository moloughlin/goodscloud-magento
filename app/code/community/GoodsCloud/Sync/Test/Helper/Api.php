<?php

/**
 * Class GoodsCloud_Sync_Test_Helper_Api
 */
class GoodsCloud_Sync_Test_Helper_Api extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @loadFixture
     */
    public function testSettings()
    {
        $helper = Mage::helper('goodscloud_sync/api');
        $this->assertEquals('http://sandbox.goodscloud.com', $helper->getUri());
        $this->assertEquals('api@goodscloud.com', $helper->getEmail());
        $this->assertEquals('!"§g$&6%ZHTRasdB', $helper->getPassword());
    }

    public function testGetIgnoredAttributes()
    {
        $helper = Mage::helper('goodscloud_sync/api');
        $this->assertContains('sku', $helper->getIgnoredAttributes());
    }
}