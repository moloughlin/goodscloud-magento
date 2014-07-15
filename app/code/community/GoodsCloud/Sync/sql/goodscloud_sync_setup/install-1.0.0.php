<?php
/* @var $this Mage_Core_Model_Resource_Setup */
$this->getConnection()->addColumn(
    $this->getTable('core/store'),
    'gc_id',
    array(
        'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'unsigned' => false,
        'comment'  => 'goodscloud channel id'
    )
);

$this->getConnection()->addIndex(
    $this->getTable('core/store'),
    $this->getConnection()->getIndexName(
        $this->getTable('core/store'),
        'gc_id',
        Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
    ),
    'gc_id',
    Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
);
