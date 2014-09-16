<?php

class GoodsCloud_Sync_Model_FirstWrite_VatRate extends GoodsCloud_Sync_Model_FirstWrite_Base
{

    /**
     * @return int
     */
    public function createAndSaveDefaultVatList()
    {
        /** @var $apiHelper GoodsCloud_Sync_Helper_Api */
        $apiHelper = Mage::helper('goodscloud_sync/api');

        $defaultVatRateId = $apiHelper->getDefaultVatRate();
        if ($defaultVatRateId) {
            return $defaultVatRateId;
        }

        $vatRate = $this->getApi()->createVatRate();

        $apiHelper->setDefaultVatRate($vatRate->getId());
        return $vatRate->getId();
    }
}
