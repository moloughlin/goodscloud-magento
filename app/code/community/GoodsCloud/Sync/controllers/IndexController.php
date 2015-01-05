<?php

class GoodsCloud_Sync_IndexController extends Mage_Core_Controller_Front_Action
{

    public function orderImportAction()
    {
        $orderIds = $this->getRequest()->getParam('order_ids');
        $api = Mage::getModel('goodscloud_sync/api');

        $orderSync = Mage::getModel('goodscloud_sync/sync_orders');
        $orderSync->setApi($api);

        $orderSync->updateOrdersById($orderIds);
    }

    public function productImportAction()
    {

    }

    public function orderExportAction()
    {

    }
}
