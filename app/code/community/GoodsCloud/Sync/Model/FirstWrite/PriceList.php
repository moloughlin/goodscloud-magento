<?php

class GoodsCloud_Sync_Model_FirstWrite_PriceList extends GoodsCloud_Sync_Model_FirstWrite_Base
{
    public function createAndSaveDefaultPriceList()
    {
        $countryCollection = Mage::getResourceModel('directory/country_collection')
            ->loadByStore();

        $countryList = array();
        foreach ($countryCollection as $country) {
            $countryList[] = $country->getIso2Code();
        }

        $this->getApi()->createPriceList(
            Mage::helper('goodscloud_sync')->__('Magento Standard Sales Price List') . '4sdfas',
            0,
            false,
            // TODO fix
            array('DE') // $countryList
        );
    }
}
