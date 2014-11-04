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
     * @var Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection
     */
    private $attributeSetCache;

    /**
     * @var Mage_Eav_Model_Resource_Entity_Attribute_Collection
     */
    private $attributeCache;

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
        $timeBeforeUpdate = $this->getCurrentDateTime();

        // get last update datetime
        $lastUpdateTime = $this->retrieveUpdateTime();

        // TODO REMOVE!!!
        $lastUpdateTime = '2007-12-12 12:12:12Z';

        // merge into big array
        $arrayToImport = $this->getProductArrayForImport(
        // get changed company products
            $this->getChangedCompanyProducts($lastUpdateTime),
            // get changed channel products
            $this->getChangedChannelProducts($lastUpdateTime)
        );

        // import via AvS
        $this->import($arrayToImport);
        // set new update datetime
        $this->saveUpdateTime($timeBeforeUpdate);
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
        if ($this->flag === null) {
            $this->flag = Mage::getModel('goodscloud_sync/sync_updateDateTime')
                ->loadSelf();
        }
    }

    /**
     * @param $lastUpdateTime
     *
     * @return array
     */
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
        /** @var $companyProductArrayGenerator GoodsCloud_Sync_Model_Sync_CompanyProduct_ArrayConstructor */
        $companyProductArrayGenerator = Mage::getModel(
            'goodscloud_sync/sync_companyProduct_arrayConstructor'
        );


        return $companyProductArrayGenerator
            ->setAttributeSetCache($this->getAttributeSetCache())
            ->setStoreViewCache(Mage::app()->getStores())
            ->setAttributeCache($this->getAttributeCache())
            ->construct($products);
    }

    /**
     *
     */
    private function getChangedChannelProducts($lastUpdateTime)
    {
        // TODO
        return array();
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

        $products = $this->api->getChannelProducts($filters);
        /** @var $channelProductArrayGenerator GoodsCloud_Sync_Model_Sync_ChannelProduct_ArrayConstructor */
        $channelProductArrayGenerator = Mage::getModel(
            'goodscloud_sync/sync_channelProduct_arrayConstructor'
        );

        return $channelProductArrayGenerator
            ->setAttributeSetCache($this->getAttributeSetCache())
            ->setStoreViewCache(Mage::app()->getStores())
            ->construct($products);
    }

    /**
     * @param array $companyProducts
     * @param array $channelProducts
     *
     * @return array
     */
    private function getProductArrayForImport(
        array $companyProducts,
        array $channelProducts
    ) {
        $import = array();

        foreach ($companyProducts as $line) {
            foreach ($line as $entry) {
                $import[] = $entry;
            }
        }

        foreach ($channelProducts as $line) {
            foreach ($line as $entry) {
                $import[] = $entry;
            }
        }
        return $import;
    }

    /**
     * @return Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection
     */
    private function getAttributeSetCache()
    {
        if ($this->attributeSetCache === null) {
            $productAttributeEntity = Mage::getModel('eav/entity_type')
                ->loadByCode('catalog_product');

            $this->attributeSetCache
                = Mage::getResourceModel('eav/entity_attribute_set_collection');

            $this->attributeSetCache->addFieldToFilter(
                'entity_type_id',
                $productAttributeEntity->getId()
            );
        }
        return $this->attributeSetCache;
    }

    /**
     * @param array $products
     */
    private function import($products)
    {
        /** @var $import AvS_FastSimpleImport_Model_Import */
        $import = Mage::getModel('fastsimpleimport/import');
        try {
            $import->processProductImport($products);
        } catch (Exception $e) {
            Mage::log($import->getErrorMessages());
        }
    }

    /**
     *
     */
    private function getAttributeCache()
    {
        if ($this->attributeCache === null) {
            $productAttributeEntity = Mage::getModel('eav/entity_type')
                ->loadByCode('catalog_product');
            $this->attributeCache
                = Mage::getResourceModel('eav/entity_attribute_collection');
            $this->attributeCache->setEntityTypeFilter($productAttributeEntity);
        }
        return $this->attributeCache;
    }
}