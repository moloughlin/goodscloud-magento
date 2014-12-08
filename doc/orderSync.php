<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../../goodscloud-sync/app/Mage.php';

Mage::app();

$api = Mage::getModel('goodscloud_sync/api');

$orderSync = Mage::getModel('goodscloud_sync/sync_orders');
$orderSync->setApi($api);

$orderSync->sync();
