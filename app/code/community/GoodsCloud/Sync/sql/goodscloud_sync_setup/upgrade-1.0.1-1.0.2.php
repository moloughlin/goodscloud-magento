<?php
/**
 * Create new column in catalog attribute table to save the property schema ids
 */
/* @var $this Mage_Core_Model_Resource_Setup */

$this->startSetup();

$this->getConnection()->addColumn(
    $this->getTable('catalog/eav_attribute'),
    'gc_property_schema_ids',
    array(
        'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'   => 255,
        'unsigned' => false,
        'comment'  => 'goodscloud property schema ids'
    )
);

$this->endSetup();
