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
     * @var string[]
     */
    private $categoryCache;

    /**
     * @param GoodsCloud_Sync_Model_Api $api
     *
     * @return $this
     */
    public function setApi(GoodsCloud_Sync_Model_Api $api)
    {
        $this->api = $api;
        return $this;
    }

    public function updateProductsById(
        array $companyProductIds,
        array $channelProductIds,
        array $companyProductViewIds,
        array $channelProductViewIds
    ) {
        $arrayToImport = array();

        // merge into big array
        $arrayToImport += $this->getProductArrayForImport(
        // get changed company products
            $this->getChangedCompanyProducts(
                $this->getIdFilter($companyProductIds)
            ),
            // get changed channel products
            $this->getChangedChannelProducts(
                $this->getIdFilter($channelProductIds)
            )
        );

        $arrayToImport += $this->getProductArrayForImport(
            $this->getChangedCompanyProductViews(
                $this->getIdFilter($companyProductViewIds)
            ),
            $this->getChangedChannelProductViews(
                $this->getIdFilter($channelProductViewIds)
            )
        );

        // import via AvS
        $this->import($arrayToImport);
    }

    /**
     *
     */
    public function updateProductsByTimestamp()
    {
        // save the time before import to make sure, the next time we get all
        // products which were updated during import
        $timeBeforeUpdate = $this->getCurrentDateTime();

        // get last update datetime
        $lastUpdateTime = $this->retrieveUpdateTime();

        $filter = $this->getTimestampFilter($lastUpdateTime);
        $this->updateProducts($filter);

        // set new update datetime
        $this->saveUpdateTime($timeBeforeUpdate);
    }

    private function updateProducts(array $filter)
    {
        $arrayToImport = array();

        // merge into big array
        $arrayToImport += $this->getProductArrayForImport(
        // get changed company products
            $this->getChangedCompanyProducts($filter),
            // get changed channel products
            $this->getChangedChannelProducts($filter)
        );

        $arrayToImport += $this->getProductArrayForImport(
            $this->getChangedCompanyProductViews($filter),
            $this->getChangedChannelProductViews($filter)
        );

        // import via AvS
        $this->import($arrayToImport);
    }

    /**
     * @param array $filters
     *
     * @return array
     */
    private function getChangedCompanyProductViews($filters)
    {
        $products = $this->api->getCompanyProductViews($filters);

        /** @var $companyProductArrayGenerator GoodsCloud_Sync_Model_Sync_Company_Product_View_ArrayConstructor */
        $companyProductArrayGenerator = Mage::getModel(
            'goodscloud_sync/sync_company_product_view_arrayConstructor'
        );

        return $companyProductArrayGenerator
            ->setAttributeSetCache($this->getAttributeSetCache())
            ->setStoreViewCache(Mage::app()->getStores())
            ->setAttributeCache($this->getAttributeCache())
            ->construct($products);
    }


    /**
     * @param array $filters
     *
     * @return array
     */
    private function getChangedChannelProductViews($filters)
    {
        $products = $this->api->getChannelProductViews($filters);

        /** @var $companyProductArrayGenerator GoodsCloud_Sync_Model_Sync_Channel_Product_View_ArrayConstructor */
        $companyProductArrayGenerator = Mage::getModel(
            'goodscloud_sync/sync_channel_product_view_arrayConstructor'
        );

        return $companyProductArrayGenerator
            ->setAttributeSetCache($this->getAttributeSetCache())
            ->setStoreViewCache(Mage::app()->getStores())
            ->setAttributeCache($this->getAttributeCache())
            ->setCategoryCache($this->getCategoryCache())
            ->setApi($this->api)
            ->construct($products);
    }

    /**
     * built category path for import
     *
     * @return string[]
     */
    private function getCategoryCache()
    {
        if ($this->categoryCache === null) {
            $categories = Mage::getResourceModel('catalog/category_collection')
                ->addAttributeToSelect('name');
            foreach ($categories as $category) {
                $path = array();
                $pathIds = $category->getPathIds();
                // remove first two levels of the category
                unset($pathIds[0], $pathIds[1]);
                foreach ($pathIds as $id) {
                    $path[] = $categories->getItemById($id)->getName();
                }
                $this->categoryCache[$category->getId()] = implode('/', $path);
            }
        }
        return $this->categoryCache;
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
     * @param array $filters
     *
     * @return array
     */
    private function getChangedCompanyProducts($filters)
    {
        $products = $this->api->getCompanyProducts($filters);
        /** @var $companyProductArrayGenerator GoodsCloud_Sync_Model_Sync_CompanyProduct_ArrayConstructor */
        $companyProductArrayGenerator = Mage::getModel(
            'goodscloud_sync/sync_company_product_arrayConstructor'
        );

        return $companyProductArrayGenerator
            ->setAttributeSetCache($this->getAttributeSetCache())
            ->setStoreViewCache(Mage::app()->getStores())
            ->setAttributeCache($this->getAttributeCache())
            ->construct($products);
    }

    /**
     * @param array $filters
     *
     * @return array
     *
     */
    private function getChangedChannelProducts($filters)
    {
        $products = $this->api->getChannelProducts($filters);
        /** @var $channelProductArrayGenerator GoodsCloud_Sync_Model_Sync_ChannelProduct_ArrayConstructor */
        $channelProductArrayGenerator = Mage::getModel(
            'goodscloud_sync/sync_channel_product_arrayConstructor'
        );

        return $channelProductArrayGenerator
            ->setAttributeSetCache($this->getAttributeSetCache())
            ->setStoreViewCache(Mage::app()->getStores())
            ->setAttributeCache($this->getAttributeCache())
            ->construct($products);
    }

    /**
     * merge both arrays to get a format of this type:
     * array(
     *  company_product, channel_product, channel_product,
     *  company_product, channel_product, channel_product,
     *  company_product, channel_product, channel_product,
     * )
     *
     *
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

        $mergedArrays = array_merge_recursive(
            $companyProducts,
            $channelProducts
        );
        foreach ($mergedArrays as $sku => $line) {
            $first = true;
            foreach ($line as $entry) {
                if (!$first) {
                    // only the first entry should an sku entry, so magento knows
                    // that we have different views for the same product
                    unset($entry['sku']);
                }
                $import[] = $entry;
                $first = false;
            }

            // only import every row once
            unset($companyProducts[$sku]);
        }

        return $import;
    }

    /**
     * @param array $products
     */
    private function import($products)
    {
        if (empty($products)) {
            return;
        }

        /** @var $import AvS_FastSimpleImport_Model_Import */
        $import = Mage::getModel('fastsimpleimport/import');
        $import->setBehavior(Mage_ImportExport_Model_Import::BEHAVIOR_REPLACE);
        $import->setUseNestedArrays(true);
        $import->setIgnoreDuplicates(false);
        $import->setUnsetEmptyFields(true);

        try {
            $import->processProductImport($products);
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::log($import->getErrorMessages());
        }
    }

    /**
     * @return Mage_Eav_Model_Resource_Entity_Attribute_Set[]
     */
    private function getAttributeSetCache()
    {
        if ($this->attributeSetCache === null) {
            $productAttributeEntity = Mage::getModel('eav/entity_type')
                ->loadByCode('catalog_product');

            $attributeSetCollection = Mage::getResourceModel(
                'eav/entity_attribute_set_collection'
            );

            $attributeSetCollection->addFieldToFilter(
                'entity_type_id',
                $productAttributeEntity->getId()
            );

            foreach ($attributeSetCollection as $attributeSet) {
                $propertySetIds
                    = json_decode($attributeSet->getGcPropertySetIds());
                foreach ($propertySetIds as $id) {
                    $this->attributeSetCache[$id] = $attributeSet;
                }
            }
        }
        return $this->attributeSetCache;
    }

    /**
     *
     */
    private function getAttributeCache()
    {
        if ($this->attributeCache === null) {
            $attributes = Mage::getResourceModel(
                'catalog/product_attribute_collection'
            );

            $this->attributeCache = array();
            foreach ($attributes as $attribute) {
                /* @var Mage_Catalog_Model_Entity_Attribute */
                $attributeCode = $attribute->getAttributeCode();
                $this->attributeCache[$attributeCode] = $attribute;
            }
        }
        return $this->attributeCache;
    }

    /**
     * @param $lastUpdateTime
     *
     * @return array
     */
    private function getTimestampFilter($lastUpdateTime)
    {
        $lastUpdateTime = '2014-12-01T13:00:31.300450+00:00';

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
        return $filters;
    }

    private function getIdFilter($ids)
    {
        return array(
            array(
                'name' => 'id',
                'op'   => 'in',
                'val'  => $ids
            )
        );
    }
}
