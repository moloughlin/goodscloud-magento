<?php
/* @var $this Mage_Catalog_Model_Resource_Setup */

$this->addAttribute(
    Mage_Catalog_Model_Category::ENTITY,
    'gc_category_ids',
    array(
        'global'          => 1,
        'required'        => 0,
        'visible'         => 0,
        'is_configurable' => 0,
    )
);
