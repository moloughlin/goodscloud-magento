<?php

class GoodsCloud_Sync_Model_Observer
{
    public function catalogProductSaveBefore(Varien_Event_Observer $observer)
    {
        $product = $observer->getProduct();
        if (!$product->getGcSave()) {
            $this->throwProductException();
        }
    }

    public function catalogCategorySaveBefore(Varien_Event_Observer $observer)
    {
        $category = $observer->getCategory();
        if (!$category->getGcSave()) {
            Mage::throwException(
                Mage::helper('goodscloud_sync')->__('Category saving is deactivated in magento, please use Goodscloud.')
            );
        }
    }

    public function catalogControllerProductDelete()
    {
        $this->throwProductException();
    }

    public function catalogProductAttributeUpdateBefore()
    {
        $this->throwProductException();
    }

    private function throwProductException()
    {
        Mage::throwException(
            Mage::helper('goodscloud_sync')->__('Product saving is deactivated in magento, please use Goodscloud.')
        );
    }

    public function catalogEntityAttributeSaveBefore(Varien_Event_Observer $observer)
    {
        /** @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
        $attribute = $observer->getAttribute();
        $attribute->getResource()->saveInSetIncluding($attribute);
        Mage::throwException(
            Mage::helper('goodscloud_sync')->__('Attribute saving is deactivated in magento, please use Goodscloud.')
        );
    }

    public function eavEntityAttributeSetSaveBefore()
    {
        Mage::throwException(
            Mage::helper('goodscloud_sync')->__('Attribute set saving is deactivated in magento, please use Goodscloud.')
        );
    }
}
