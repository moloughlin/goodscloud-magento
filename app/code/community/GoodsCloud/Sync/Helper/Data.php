<?php

class GoodsCloud_Sync_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getLanguageByStoreView(Mage_Core_Model_Store $view)
    {
        return strstr(Mage::getStoreConfig('general/locale/code', $view), '_', true);
    }

    public function getCurrencyByStoreView(Mage_Core_Model_Store $view)
    {
        $currentCurrency = Mage::app()->getStore($view)->getCurrentCurrencyCode();
        return Mage::app()->getLocale()->currency($currentCurrency)->getShortName();
    }
}
