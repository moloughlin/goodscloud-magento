<?php
/* @var $this Mage_Customer_Model_Resource_Setup */
$this->startSetup();

$this->addAttribute(
    'customer',
    'gc_consumer_id',
    array(
        'type'     => 'int',
        'label'    => 'Goodscloud ID',
        'required' => 0,
        'visible'  => 0,
    )
);

$this->endSetup();
