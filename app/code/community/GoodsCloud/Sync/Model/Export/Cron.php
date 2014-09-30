<?php

class GoodsCloud_Sync_Model_Export_Cron
{
    public function exportOrders()
    {
        $api = Mage::getModel('goodscloud_sync/api');
        $orderExport = Mage::getModel('goodscloud_sync/export_order');
        $orderExport->setApi($api);
    }
}
