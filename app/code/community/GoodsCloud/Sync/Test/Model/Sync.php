<?php

class GoodsCloud_Sync_Test_Model_Sync extends EcomDev_PHPUnit_Test_Case
{
    public function testSyncWithGoodscloud()
    {
        $this->markTestSkipped();
        // TODO add api
        $sync = Mage::getModel('goodscloud_sync/sync');

        $products = $this->getModelMock(
            'goodscloud_sync/sync_products', array('updateProductsByTimestamp')
        );

        $products->expects($this->once())
            ->method('updateProductsByTimestamp');

        $sync->syncWithGoodscloud();
    }
}
