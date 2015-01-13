<?php

class GoodsCloud_Sync_IndexController extends Mage_Core_Controller_Front_Action
{

    public function orderImportAction()
    {
        $orderIds = $this->getRequest()->getParam('order_ids', array());
        if (!is_array($orderIds)) {
            $this->getResponse()->setBody('order_ids must be an array of ids.');
            return;
        }
        $api = Mage::getModel('goodscloud_sync/api');

        $orderSync = Mage::getModel('goodscloud_sync/sync_orders');
        $orderSync->setApi($api);

        $orderSync->updateOrdersById($orderIds);

        $this->getResponse()->setBody('Success.');
    }

    public function productImportAction()
    {
        $companyProductIds = $this->getRequest()
            ->getParam('company_product_ids', array());
        $channelProductIds = $this->getRequest()
            ->getParam('channel_product_ids', array());
        $companyProductViewIds = $this->getRequest()
            ->getParam('company_product_view_ids', array());
        $channelProductViewIds = $this->getRequest()
            ->getParam('channel_product_view_ids', array());

        if (!is_array($companyProductIds) && !is_array($companyProductIds)
            && !is_array($channelProductViewIds)
            && !is_array($channelProductIds)
        ) {
            $this->getResponse()
                ->setBody('company_product_ids, company_product_view_ids, channel_product_view_ids and channel_product_ids must be an array of ids.');
            return;
        }

        $api = Mage::getModel('goodscloud_sync/api');

        Mage::getModel('goodscloud_sync/sync_products')->setApi($api)
            ->updateProductsById(
                $companyProductIds,
                $companyProductIds,
                $companyProductViewIds,
                $channelProductViewIds
            );

        $this->getResponse()->setBody('Success.');
    }

    public function orderExportAction()
    {
        $api = Mage::getModel('goodscloud_sync/api');

        Mage::getModel('goodscloud_sync/export_order')->setApi($api)
            ->exportOrders();
    }
}
