<?php

class GoodsCloud_Sync_Model_FirstWrite_PriceList
    extends GoodsCloud_Sync_Model_FirstWrite_Base
{

    const XML_COUNTRY_CODE_LIST = 'goodscloud_sync/api/goodscloud_country_codes';

    /**
     * @return int
     */
    public function createAndSaveDefaultPriceList()
    {
        /** @var $apiHelper GoodsCloud_Sync_Helper_Api */
        $apiHelper = Mage::helper('goodscloud_sync/api');

        $defaultPriceListId = $apiHelper->getDefaultPriceListId();
        if ($defaultPriceListId) {
            return $defaultPriceListId;
        }

        $countryCollection
            = Mage::getResourceModel('directory/country_collection')
            ->loadByStore();

        $countryList = array();
        foreach ($countryCollection as $country) {
            $countryList[] = $country->getIso2Code();
        }

        $priceList = $this->getApi()->createPriceList(
            Mage::helper('goodscloud_sync')
                ->__('Magento Standard Sales Price List'),
            0,
            false,
            $this->cleanUpCountryList($countryList)
        );

        $apiHelper->setDefaultPriceListId($priceList->getId());
        return $priceList->getId();
    }

    /**
     * @param array $magentoCodes
     *
     * @return array
     */
    private function cleanUpCountryList(array $magentoCodes)
    {
        $goodscloudCodes = array_keys(Mage::getStoreConfig(self::XML_COUNTRY_CODE_LIST));
        return array_values(array_intersect($magentoCodes, $goodscloudCodes));
    }
}
