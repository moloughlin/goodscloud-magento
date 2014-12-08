<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../../goodscloud-sync/app/Mage.php';

Mage::app();

$api = Mage::getModel('goodscloud_sync/api');

do {
    $orderItems = $api->getOrderItem();
    foreach ($orderItems as $id => $orderItem) {
        $api->deleteOrderItem($id);
    }
} while ($orderItems->getLastPageNumber() > 1);

do {
    $orders = $api->getOrders();
    foreach ($orders as $id => $order) {
        $api->deleteOrder($id);
    }
} while ($orders->getLastPageNumber() > 1);

do {
    $invoices = $api->getInvoices();
    foreach ($invoices as $id => $invoice) {
        $api->deleteInvoice($id);
    }
} while ($invoices->getLastPageNumber() > 1);

do {
    $consumers = $api->getConsumers();
    foreach ($consumers as $id => $consumer) {
        $api->deleteConsumer($id);
    }
} while ($consumers->getLastPageNumber() > 1);

Mage::getModel('core/resource')->getConnection('core_write')->query('UPDATE sales_flat_order SET gc_exported = NULL');
Mage::getModel('core/resource')->getConnection('core_write')->query('DELETE FROM customer_entity_int WHERE attribute_id IN (SELECT attribute_id FROM eav_attribute WHERE attribute_code = \'gc_consumer_id\')');
