<?php
/* @var $this Mage_Catalog_Model_Resource_Setup */

$applyToAllPhysical = array(
    Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
    Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
    Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
    Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
);

$this->addAttribute(
    Mage_Catalog_Model_Product::ENTITY,
    'gc_product_ids',
    array(
        'user_defined'    => 0,
        'global'          => 1,
        'required'        => 0,
        'visible'         => 0,
        'apply_to'        => implode(',', $applyToAllPhysical),
        'is_configurable' => 0,
    )
);
