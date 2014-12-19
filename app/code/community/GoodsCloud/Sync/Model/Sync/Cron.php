<?php

class GoodsCloud_Sync_Model_Sync_Cron
{
    public function syncProducts()
    {
        $api = Mage::getModel('goodscloud_sync/api');

        $sync = Mage::getModel('goodscloud_sync/sync');
        $sync->setApi($api);
        $sync->syncWithGoodscloud();
    }

    public function syncOrders()
    {
        $api = Mage::getModel('goodscloud_sync/api');

        $orderSync = Mage::getModel('goodscloud_sync/sync_orders');
        $orderSync->setApi($api);

        $orderSync->sync();
    }

    public function sync()
    {
        $this->syncOrders();
        $this->syncProducts();
    }
}
