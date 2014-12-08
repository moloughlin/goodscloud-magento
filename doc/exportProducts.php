<?php
require __DIR__ . '/../../goodscloud-sync/app/Mage.php';

Mage::app();

$api = Mage::getModel('goodscloud_sync/api');

$firstWrite = Mage::getModel('goodscloud_sync/firstWrite');
$firstWrite->writeMagentoToGoodscloud();
