<?php
/* @var $this Mage_Catalog_Model_Resource_Setup */

$applyToAll = array(
    Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
    Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
    Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
    Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
    Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL,
    Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE,
);

$this->updateAttribute(
    Mage_Catalog_Model_Product::ENTITY,
    'gc_product_ids',
    'apply_to',
    implode(',', $applyToAll)
);
