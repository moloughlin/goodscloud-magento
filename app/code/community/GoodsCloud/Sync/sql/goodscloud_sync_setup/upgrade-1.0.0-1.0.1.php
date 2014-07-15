<?php
/**
 * Create new column in attribute_set table to save the property set ids
 */
/* @var $this Mage_Core_Model_Resource_Setup */

$this->startSetup();

$this->getConnection()->addColumn(
    $this->getTable('eav/attribute_set'),
    'gc_property_set_ids',
    array(
        'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'   => 255,
        'unsigned' => false,
        'comment'  => 'goodscloud property set ids'
    )
);

$this->endSetup();
