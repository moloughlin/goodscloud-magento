<?php

class GoodsCloud_Sync_Model_FirstWrite_PriceList extends GoodsCloud_Sync_Model_FirstWrite_Base
{
    public function createAndSaveDefaultPriceList()
    {
        /** @var $apiHelper GoodsCloud_Sync_Helper_Api */
        $apiHelper = Mage::helper('goodscloud_sync/api');

        $defaultPriceListId = $apiHelper->getDefaultPriceList();
        if ($defaultPriceListId) {
            return $defaultPriceListId;
        }

        $countryCollection = Mage::getResourceModel('directory/country_collection')
            ->loadByStore();

        $countryList = array();
        foreach ($countryCollection as $country) {
            $countryList[] = $country->getIso2Code();
        }

        $priceList = $this->getApi()->createPriceList(
            Mage::helper('goodscloud_sync')->__('Magento Standard Sales Price List'),
            0,
            false,
            array('DE')
            #$countryList
        );

        $apiHelper->setDefaultPriceList($priceList->getId());
        return $priceList->getId();
    }
}
