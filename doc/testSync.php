<?php
require __DIR__ . '/../../goodscloud-sync/app/Mage.php';

Mage::app();
$api = Mage::getModel('goodscloud_sync/api');

$sync = Mage::getModel('goodscloud_sync/sync');
$sync->setApi($api);
$sync->syncWithGoodscloud();
