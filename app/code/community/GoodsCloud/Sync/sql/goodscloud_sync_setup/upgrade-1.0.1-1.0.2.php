<?php
/**
 * Create new column in attribute_set table to save the property set ids
 */
/* @var $this Mage_Core_Model_Resource_Setup */

$this->startSetup();

$this->getConnection()->addColumn(
    $this->getTable('customer/eav_attribute'),
    'gc_property_schema_ids',
    array(
        'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'   => 255,
        'unsigned' => false,
        'comment'  => 'goodscloud property set ids'
    )
);

$this->endSetup();