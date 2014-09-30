<?php
/* @var $this Mage_Sales_Model_Resource_Setup */
$this->startSetup();

$this->addAttribute(
    'order',
    'gc_exported',
    array(
        'type'    => 'int',
        'comment' => 'Is order to goodscloud exported?',
        'default' => 0,
    )
);

$this->endSetup();
