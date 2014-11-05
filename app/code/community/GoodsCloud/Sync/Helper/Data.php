<?php

class GoodsCloud_Sync_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * get language code from store view in form en
     *
     * @param Mage_Core_Model_Store $view
     *
     * @return string
     */
    public function getLanguageByStoreView(Mage_Core_Model_Store $view)
    {
        return strstr(Mage::getStoreConfig('general/locale/code', $view), '_',
            true);
    }

    /**
     * get currency code from store view
     *
     * @param Mage_Core_Model_Store $view
     *
     * @return string
     */
    public function getCurrencyByStoreView(Mage_Core_Model_Store $view)
    {
        $currentCurrency = Mage::app()->getStore($view)
            ->getCurrentCurrencyCode();
        return Mage::app()->getLocale()->currency($currentCurrency)
            ->getShortName();
    }

    public function getChannelNameByStoreView(Mage_Core_Model_Store $view)
    {
        return $view->getName();
    }
}
