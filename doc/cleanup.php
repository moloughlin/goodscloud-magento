<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../../goodscloud-sync/app/Mage.php';

Mage::app();

$api = Mage::getModel('goodscloud_sync/api');

do {
    $shipmentItems = $api->getShipmentItems();
    foreach ($shipmentItems as $id => $item) {
        $api->deleteShipmentItem($id);
    }
} while ($shipmentItems->getLastPageNumber() > 1);

do {
    $logisticOrderItems = $api->getLogisticOrderItem();
    foreach ($logisticOrderItems as $id => $item) {
        $api->deleteLogisticOrderItem($id);
    }
} while ($logisticOrderItems->getLastPageNumber() > 1);

do {
    $creditNoteItems = $api->getCreditNoteItems();
    foreach ($creditNoteItems as $id => $item) {
        $api->deleteCreditNoteItem($id);
    }
} while ($creditNoteItems->getLastPageNumber() > 1);

do {
    $invoiceItems = $api->getInvoiceItems();
    foreach ($invoiceItems as $id => $item) {
        $api->deleteInvoiceItem($id);
    }
} while ($invoiceItems->getLastPageNumber() > 1);

do {
    $orderItems = $api->getOrderItem();
    foreach ($orderItems as $id => $orderItem) {
        $api->deleteOrderItem($id);
    }
} while ($orderItems->getLastPageNumber() > 1);

do {
    $shipments = $api->getShipments();
    foreach ($shipments as $id => $item) {
        $api->deleteShipment($id);
    }
} while ($shipments->getLastPageNumber() > 1);

do {
    $logisticOrders = $api->getLogisticOrder();
    foreach ($logisticOrders as $id => $item) {
        $api->deleteLogisticOrder($id);
    }
} while ($logisticOrders->getLastPageNumber() > 1);

do {
    $invoices = $api->getInvoices();
    foreach ($invoices as $id => $invoice) {
        $api->deleteInvoice($id);
    }
} while ($invoices->getLastPageNumber() > 1);

do {
    $creditNotes = $api->getCreditNotes();
    foreach ($creditNotes as $id => $creditNote) {
        $api->deleteCreditNote($id);
    }
} while ($creditNotes->getLastPageNumber() > 1);

do {
    $orders = $api->getOrders();
    foreach ($orders as $id => $order) {
        $api->deleteOrder($id);
    }
} while ($orders->getLastPageNumber() > 1);

do {
    $consumers = $api->getConsumers();
    foreach ($consumers as $id => $consumer) {
        $api->deleteConsumer($id);
    }
} while ($consumers->getLastPageNumber() > 1);

do {
    $companyProducts = $api->getCompanyProducts();
    foreach ($companyProducts as $id => $product) {
        $api->deleteCompanyProduct($id);
    }
} while ($companyProducts->getLastPageNumber() > 1);

do {
    $channelProducts = $api->getChannelProducts();
    foreach ($channelProducts as $id => $product) {
        $api->deleteChannelProduct($id);
    }

} while ($channelProducts->getLastPageNumber() > 1);

do {
    $descriptions = $api->getProductDescriptions();
    foreach ($descriptions as $id => $product) {
        $api->deleteProductDescription($id);
    }
} while ($descriptions->getLastPageNumber() > 1);

do {
    $images = $api->getProductImages();
    foreach ($images as $id => $image) {
        $api->deleteProductImage($id);
    }
} while ($images->getLastPageNumber() > 1);

do {
    $views = $api->getChannelProductViews();
    foreach ($views as $id => $view) {
        $api->deleteChannelProductView($id);
    }
} while ($views->getLastPageNumber() > 1);

do {
    $prices = $api->getPrices();
    foreach ($prices as $id => $price) {
        $api->deletePrice($id);
    }
} while ($prices->getLastPageNumber() > 1);

$filter = array('name' => 'parent_id', 'op' => 'is_null', 'val' => 'any');
$categories = $api->getCategories($filter);
foreach ($categories as $id => $category) {
    if (!$category->getParentId()) {
        $api->deleteCategory($id);
    }
}

do {
    $priceLists = $api->getPriceLists();
    foreach ($priceLists as $id => $priceList) {
        $api->deletePriceList($id);
    }
} while ($priceLists->getLastPageNumber() > 1);

do {
    $propertySchemas = $api->getPropertySchemas();
    foreach ($propertySchemas as $id => $schema) {
        $api->deletePropertySchemas($id);
    }
} while ($propertySchemas->getLastPageNumber() > 1);

do {
    $propertSets = $api->getPropertySets();
    foreach ($propertSets as $id => $set) {
        $api->deletePropertySet($id);
    }
} while ($propertSets->getLastPageNumber() > 1);

do {
    $vatRates = $api->getVatRates();
    foreach ($vatRates as $id => $vatRate) {
        $api->deleteVatRate($id);
    }
} while ($vatRates->getLastPageNumber() > 1);

do {
    $channels = $api->getChannels();
    foreach ($channels as $id => $channel) {
        $api->deleteChannel($id);
    }
} while ($channels->getLastPageNumber() > 1);

do {
    $companyProductViews = $api->getCompanyProductViews();
    foreach ($companyProductViews as $id => $view) {
        $api->deleteCompanyProductview($id);
    }
} while ($companyProductViews->getLastPageNumber() > 1);
