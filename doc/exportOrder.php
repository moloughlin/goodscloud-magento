<?php
require __DIR__ . '/../../goodscloud-sync/app/Mage.php';

Mage::app();

$api = Mage::getModel('goodscloud_sync/api');

$cron = Mage::getModel('goodscloud_sync/export_cron');
$cron->exportOrders();
