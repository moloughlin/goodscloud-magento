<?php
require __DIR__ . '/../../goodscloud-sync/app/Mage.php';

Mage::app();
$api = Mage::getModel('goodscloud_sync/api');

$sync = Mage::getModel('goodscloud_sync/sync_products');
$sync->setApi($api);
$sync->updateProductsById(
    array(54135, 54134, 54136),
    array(),
    array(31994),
    array(36150, 36232, 36314)
);
