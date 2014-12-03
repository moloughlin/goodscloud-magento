<?php

class GoodsCloud_Sync_Model_Sync_Orders
{

    const ORDER_ROUTING_STATUS_ON_HOLD = 'on hold';
    const ORDER_ROUTING_STATUS_CANCELED = 'canceled';
    const ORDER_ROUTING_STATUS_MIXED = 'mixed';

    CONST SHIPMENT_DELIVERY_STATUS_OPEN_BOX = 'open_box';
    CONST SHIPMENT_DELIVERY_STATUS_AWAITING_PICKUP = 'awaiting_pickup';
    CONST SHIPMENT_DELIVERY_STATUS_SHIPPED = 'shipped';
    CONST SHIPMENT_DELIVERY_STATUS_DELAYED = 'delayed';
    CONST SHIPMENT_DELIVERY_STATUS_UNDELIVERABLE = 'undeliverable';
    CONST SHIPMENT_DELIVERY_STATUS_LOST = 'lost';
    CONST SHIPMENT_DELIVERY_STATUS_DELIVERED = 'delivered';

    private $statusesToCreateShipment;

    /**
     *
     */
    function __construct()
    {
        $this->statusesToCreateShipment = array(
            self::SHIPMENT_DELIVERY_STATUS_SHIPPED,
            self::SHIPMENT_DELIVERY_STATUS_DELAYED,
            self::SHIPMENT_DELIVERY_STATUS_UNDELIVERABLE,
            self::SHIPMENT_DELIVERY_STATUS_LOST,
            self::SHIPMENT_DELIVERY_STATUS_DELIVERED,
        );
    }


    /**
     * @var GoodsCloud_Sync_Model_Api
     */
    private $api;

    /**
     * @var GoodsCloud_Sync_Model_Sync_OrderSyncDateTime
     */
    private $flag;

    public function sync()
    {
        $lastUpdateTime = $this->retrieveUpdateTime();

        // get all orders to know the onhold order items and canceld ones
        $this->updateOrders($lastUpdateTime);
    }

    /**
     * @param string $lastUpdateTime
     *
     * @todo to speed up, collect all ids and process them afterwards (so a collection for loading can be used)
     */
    private function updateOrders($lastUpdateTime)
    {
        $filter = $this->getUpdatedFilter($lastUpdateTime);
        $orders = $this->api->getOrders($filter);
        foreach ($orders as $order) {
            /* @var $order GoodsCloud_Sync_Model_Api_Order */
            switch ($order->getRoutingStatus()) {
                case self::ORDER_ROUTING_STATUS_CANCELED:
                    $this->cancelOrder($order->getExternalIdentifier());
                    break;
                case self::ORDER_ROUTING_STATUS_ON_HOLD:
                    $this->putOrderOnHold($order->getExternalIdentifier());
                    break;
                case self::ORDER_ROUTING_STATUS_MIXED:
                    $this->checkOrderItems($order);
                    break;
            }

            $this->createInvoices($order);
            $this->createShipments($order);
            $this->createCreditNotes($order);
        }
    }


    /**
     * @param array                           $gcInvoice
     * @param GoodsCloud_Sync_Model_Api_Order $gcOrder
     *
     * @throws Mage_Core_Exception
     */
    private function updateInvoice(
        array $gcInvoice,
        GoodsCloud_Sync_Model_Api_Order $gcOrder
    ) {
        $qtys = $this->getQtys($gcInvoice, $gcOrder, 'invoice_items');

        if (empty($qtys)) {
            return;
        }

        $qtys = $this->getParentOrderItemIds($qtys);

        try {
            Mage::getModel('sales/order_invoice_api')
                ->create($gcOrder->getExternalIdentifier(), $qtys);

        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
        }
    }

    private function getParentOrderItemIds(array $qtys)
    {
        $orderItems = Mage::getResourceModel('sales/order_item_collection')
            ->addIdFilter(array_keys($qtys))
            ->addFieldToFilter('parent_item_id', array('notnull' => true));

        foreach ($orderItems as $item) {
            $qtys[$item->getParentItemId()] = $qtys[$item->getId()];
            unset($qtys[$item->getId()]);
        }

        return $qtys;
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Order $gcOrder
     */
    private function checkOrderItems(GoodsCloud_Sync_Model_Api_Order $gcOrder)
    {
        foreach ($gcOrder->getOrderItems() as $item) {
            if ($item['routing_status'] == self::ORDER_ROUTING_STATUS_CANCELED
            ) {
                /** @var $order Mage_Sales_Model_Order */
                $order = $this->getOrder($gcOrder->getExternalIdentifier());
                /** @var $item Mage_Sales_Model_Order_Item */
                $item = $order->getItemById($item['external_identifier']);
                $item->cancel();
                $item->save();
            }
        }

        if (isset($order)) {
            $cancelOrder = true;
            // check if order can be canceled when it was loaded
            foreach ($order->getAllItems() as $item) {
                if ($item->getQtyOrdered() != $item->getQtyCanceled()) {
                    $cancelOrder = false;
                }
            }

            if ($cancelOrder) {
                $order->cancel();
                $order->save();
            }
        }
    }

    /**
     * @param string $orderId
     *
     * @throws Exception
     */
    private function putOrderOnHold($orderId)
    {
        try {
            $order = $this->getOrder($orderId);
            $order->hold();
            $order->save();
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @param string $orderId
     */
    private function cancelOrder($orderId)
    {
        $order = $this->getOrder($orderId);
        if ($order->canUnhold()) {
            $order->unhold();
        }
        $order->cancel();
        $order->save();
        if (!$order->isCanceled()) {
            // tbh I don't know when this should happen, so no precaution
            Mage::throwException('Order could not be canceled');
        }
    }

    private function getUpdatedFilter($timestamp)
    {
        return array(
            array(
                //'name'  => 'updated',
                //'op'    => '<=',
                //'value' => $timestamp
                'name' => 'external_identifier',
                'op'   => 'eq',
                'val'  => '100000201'
            )
        );
    }

    /**
     * @param GoodsCloud_Sync_Model_Api $api
     */
    public function setApi($api)
    {
        $this->api = $api;
    }

    /**
     * @return string
     */
    private function retrieveUpdateTime()
    {
        if ($this->flag === null) {
            $this->initUpdateDateTime();
        }

        return $this->flag->getFlagData();
    }

    /**
     * @param string $timeBeforeUpdateRan
     */
    private function saveUpdateTime($timeBeforeUpdateRan)
    {
        if ($this->flag === null) {
            $this->initUpdateDateTime();
        }
        $this->flag->setFlagData($timeBeforeUpdateRan);
        $this->flag->save();
    }

    /**
     *
     */
    private function initUpdateDateTime()
    {
        if ($this->flag === null) {
            $this->flag = Mage::getModel(
                'goodscloud_sync/sync_orderSyncDateTime'
            )->loadSelf();
        }
    }

    /**
     * @param string $orderId
     *
     * @return Mage_Sales_Model_Order
     */
    private function getOrder($orderId)
    {
        return $order = Mage::getModel('sales/order')
            ->load($orderId, 'increment_id');
    }

    /**
     * @param $shipment
     *
     * @return bool
     */
    private function createShipmentForShipment($shipment)
    {
        return in_array($shipment['delivery_status'],
            $this->statusesToCreateShipment);
    }

    /**
     * @param array                           $gcInvoiceShipmentOrCreditNote
     * @param GoodsCloud_Sync_Model_Api_Order $gcOrder
     * @param string                          $itemKey
     *
     * @return array
     */
    private function getQtys(
        array $gcInvoiceShipmentOrCreditNote,
        GoodsCloud_Sync_Model_Api_Order $gcOrder,
        $itemKey
    ) {
        $qtys = array();

        foreach ($gcInvoiceShipmentOrCreditNote[$itemKey] as $item) {
            $orderItems = $gcOrder->getOrderItems();
            $magentoOrderItemId
                = $orderItems[$item['order_item_id']]['external_identifier'];
            $qtys[$magentoOrderItemId] = $item['quantity'];
        }
        return $qtys;
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Order $order
     */
    private function createInvoices(GoodsCloud_Sync_Model_Api_Order $order)
    {
        // get all invoices to create magento invoices
        foreach ($order->getInvoices() as $invoice) {
            if ($invoice['final']) {
                $this->updateInvoice($invoice, $order);
            }
        }
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Order $order
     */
    private function createShipments(GoodsCloud_Sync_Model_Api_Order $order)
    {
        // get all shipments to create magento shipments
        foreach ($order->getShipments() as $shipment) {
            if ($this->createShipmentForShipment($shipment)) {
                $this->updateShipment($shipment, $order);
            }
        }
    }

    private function updateShipment(
        array $gcShipment,
        GoodsCloud_Sync_Model_Api_Order $gcOrder
    ) {
        $qtys = $this->getQtys($gcShipment, $gcOrder, 'shipment_items');

        if (empty($qtys)) {
            return;
        }

        $qtys = $this->getParentOrderItemIds($qtys);

        try {
            Mage::getModel('sales/order_invoice_api')
                ->create($gcOrder->getExternalIdentifier(), $qtys);

        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Order $order
     */
    private function createCreditNotes(GoodsCloud_Sync_Model_Api_Order $order)
    {
        // finally get all credit notes to generate credit memos
        foreach ($order->getCreditNotes() as $creditNote) {
            if ($creditNote['final']) {
                $this->updateCreditNote($creditNote, $order);
            }
        }
    }

    /**
     * @param array                           $gcCreditNote
     * @param GoodsCloud_Sync_Model_Api_Order $gcOrder
     */
    private function updateCreditNote(
        array $gcCreditNote,
        GoodsCloud_Sync_Model_Api_Order $gcOrder
    ) {
        $qtys = $this->getQtys($gcCreditNote, $gcOrder, 'credit_note_items');

        if (empty($qtys)) {
            return;
        }

        $qtys = $this->getParentOrderItemIds($qtys);

        try {
            Mage::getModel('sales/order_creditmemo_api_v2')
                ->create($gcOrder->getExternalIdentifier(), $qtys);

        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
        }
    }
}
