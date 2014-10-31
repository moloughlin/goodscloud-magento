<?php

class GoodsCloud_Sync_Model_Sync_Products
{
    /**
     * @var GoodsCloud_Sync_Model_Sync_UpdateDateTime
     */
    private $flag;

    /**
     * @var GoodsCloud_Sync_Model_Api
     */
    private $api;

    /**
     * @param GoodsCloud_Sync_Model_Api $api
     */
    public function setApi(GoodsCloud_Sync_Model_Api $api)
    {
        $this->api = $api;
    }

    public function updateProducts()
    {
        // save the time before import to make sure, the next time we get all
        // products which were updated during import
        $timeBeforeUpdateRan = $this->getCurrentDateTime();

        // get last update datetime
        $lastUpdateTime = $this->retrieveUpdateTime();

        // get changed company products
        $this->getChangedCompanyProducts($lastUpdateTime);

        // get changed channel products
        $this->getChangedChannelProducts($lastUpdateTime);

        // merge into big array
        $this->getProductArrayForImport();

        // import via AvS
        // set new update datetime
        $this->saveUpdateTime($timeBeforeUpdateRan);
    }

    /**
     *
     * now is a wrapper for date, date is timezone aware and magento takes care
     * of the timezone
     *
     * @return string
     */
    private function getCurrentDateTime()
    {
        return now();
    }

    /**
     * @return string
     */
    private function retrieveUpdateTime()
    {
        if ($this->flag === null) {
            $this->initUpdateDateTime();
        }

        return $this->flag->getFlagData();
    }

    /**
     * @param string $timeBeforeUpdateRan
     */
    private function saveUpdateTime($timeBeforeUpdateRan)
    {
        if ($this->flag === null) {
            $this->initUpdateDateTime();
        }
        $this->flag->setFlagData($timeBeforeUpdateRan);
        $this->flag->save();
    }

    /**
     *
     */
    private function initUpdateDateTime()
    {
        $this->flag = Mage::getModel('goodscloud_sync/sync_updateDateTime')
            ->loadSelf();
    }

    private function getChangedCompanyProducts($lastUpdateTime)
    {
        $filters = array();
        if ($lastUpdateTime) {
            $filters = array(
                array(
                    'name' => 'updated',
                    'op'   => '>=',
                    'val'  => $lastUpdateTime
                )
            );
        }

        $products = $this->api->getCompanyProducts($filters);
        $companyProductArrayGenerator = Mage::getModel(
            'goodscloud_sync/sync_companyProduct_arrayConstructor'
        );


        $companyProductArrayGenerator
            ->setAttributeSetCache(array())
            ->construct($products);
    }

    private function getChangedChannelProducts()
    {

    }

    private function getProductArrayForImport()
    {

    }
}
