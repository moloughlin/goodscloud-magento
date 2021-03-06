<?php

/**
 * Class GoodsCloud_Sync_Model_Export_Order
 */
class GoodsCloud_Sync_Model_Export_Order
{
    /**
     * @var GoodsCloud_Sync_Model_Export_Customer
     */
    private $customerExporter;
    /**
     * @var GoodsCloud_Sync_Model_Api
     */

    private $api;

    /**
     *
     */
    public function exportOrders()
    {
        // TODO get all customer ids and load them in a collection to have them
        // by hand instead of loading every single customer
        $orders = $this->getOrdersToExport();
        foreach ($orders as $order) {
            try {
                $gcOrder = $this->export($order);
                $order->setGcExported($gcOrder->getId())->save();
            } catch (Mage_Core_Exception $e) {
                Mage::logException($e);
            }
        }
    }

    private function getOrdersToExport()
    {
        $orderCollection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter(
                'gc_exported',
                array(
                    array('null' => true),
                    array('eq' => 0),
                )
            );

        return $orderCollection;
    }

    private function export(Mage_Sales_Model_Order $order)
    {
        $gcConsumerId = $this->getConsumerId($order);
        return $this->api->createOrder($order, $gcConsumerId);
    }

    /**
     * @param GoodsCloud_Sync_Model_Api $api
     *
     * @return $this
     */
    public function setApi(GoodsCloud_Sync_Model_Api $api)
    {
        $this->api = $api;
        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return int
     */
    private function getConsumerId(Mage_Sales_Model_Order $order)
    {
        $this->initCustomerExporter();
        return $this->customerExporter->exportByOrder($order);
    }

    private function initCustomerExporter()
    {
        if ($this->customerExporter === null) {
            $this->customerExporter
                = Mage::getModel('goodscloud_sync/export_customer');
            $this->customerExporter->setApi($this->api);
        }
    }
}
