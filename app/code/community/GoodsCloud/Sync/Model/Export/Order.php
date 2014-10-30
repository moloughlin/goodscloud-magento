<?php

/**
 * Class GoodsCloud_Sync_Model_Export_Order
 */
class GoodsCloud_Sync_Model_Export_Order
{
    private $customerExporter;
    /**
     * @var GoodsCloud_Sync_Model_Api
     */

    private $api;

    public function exportOrders()
    {
        $orders = $this->getOrdersToExport();
        // TODO get all customer ids and load them in a collection instead of every single customer
        foreach ($orders as $order) {
            try {
                $this->export($order);
            } catch (Mage_Core_Exception $e) {
                Mage::logException($e);
                // TODO handle exception
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
        $this->api->createOrder($order, $gcConsumerId);
    }

    /**
     * @param GoodsCloud_Sync_Model_Api $api
     */
    public function setApi(GoodsCloud_Sync_Model_Api $api)
    {
        $this->api = $api;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return int
     */
    private function getConsumerId(Mage_Sales_Model_Order $order)
    {
        $this->customerExporter = Mage::getModel('goodscloud_sync/export_customer');
        $this->customerExporter->setApi($this->api);
        return $this->customerExporter->exportByOrder($order);
    }
}
