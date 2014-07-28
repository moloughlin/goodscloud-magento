<?php

class GoodsCloud_Sync_Model_Observer
{
    public function catalogProductSaveBefore(Varien_Event_Observer $observer)
    {
        $product = $observer->getProduct();
        if (!$product->getGcSave()) {
            Mage::throwException(
                Mage::helper('goodscloud_sync')->__('Product saving is deactivated in magento, please use Goodscloud.')
            );
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
}
