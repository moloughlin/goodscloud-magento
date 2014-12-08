<?php
require __DIR__ . '/../../goodscloud-sync/app/Mage.php';

Mage::app();
$setup = Mage::getModel('eav/entity_setup', 'core_setup');

$setup->addAttribute('catalog_product', 'gtin', array(
        'label'    => 'GTIN',
        'required' => false,
        'unique'   => true,
    )
);

$attributeId = $setup->getAttribute('catalog_product', 'gtin')['attribute_id'];
$attributeSetId = $setup->getAttributeSetId('catalog_product', 'Default');
//Get attribute group info
$attributeGroupId = $setup->getAttributeGroup('catalog_product',
    $attributeSetId, 'General');
//add attribute to a set
$setup->addAttributeToSet('catalog_product', $attributeSetId, $attributeGroupId,
    $attributeId);

$allAttributeSetIds = $setup->getAllAttributeSetIds('catalog_product');
foreach ($allAttributeSetIds as $attributeSetId) {
    try {
        $attributeGroupId = $setup->getAttributeGroup('catalog_product',
            $attributeSetId, 'General');
    } catch (Exception $e) {
        $attributeGroupId
            = $setup->getDefaultAttributeGroupId('catalog/product',
            $attributeSetId);
    }
    $setup->addAttributeToSet('catalog_product', $attributeSetId,
        $attributeGroupId, $attributeId);
}
