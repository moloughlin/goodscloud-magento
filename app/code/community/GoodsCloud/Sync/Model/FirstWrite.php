<?php

class GoodsCloud_Sync_Model_FirstWrite
{
    /**
     * @var GoodsCloud_Sync_Model_Api
     */
    private $api;

    /**
     * do all the things which are needed, when magento and goodscloud are the first time connected
     */
    public function writeMagentoToGoodscloud()
    {
        try {

            /* @var $emulation Mage_Core_Model_App_Emulation */
            $emulation = Mage::getModel('core/app_emulation');
            $initialEnvironmentInfo = $emulation->startEnvironmentEmulation(
                Mage_Core_Model_App::ADMIN_STORE_ID,
                Mage_Core_Model_App_Area::AREA_ADMINHTML
            );

            $this->checkInstalled();

            Mage::log('Init API');

            // TODO replace by setter
            $this->initApi();

            Mage::log('Writing current time for updates');
            $this->saveUpdateDateTime();

            Mage::log('get and save company id');
            $this->getAndSaveCompanyId();


            // create default vat rate
            Mage::log('create default vat rate');
            $this->createDefaultVatRate();

            // Add a Channel for every StoreView
            Mage::log(' Add a Channel for every StoreView');
            $this->createChannelsFromStoreView();

            // Add every AttributeSet as PropertySet to every Channel
            Mage::log('Add every AttributeSet as PropertySet to every Channel');
            $this->createPropertySetsFromAttributeSets();

            // Add every Attribute as PropertySchema to every PropertySet
            Mage::log('Add every Attribute as PropertySchema to every PropertySet');
            $this->createPropertySchemasFromAttributes();

            // Map all PropertySchemas to the corresponding PropertySets
            Mage::log('Map all PropertySchemas to the corresponding PropertySets');
            $this->mapPropertySchemasToPropertySets();

            // Copy the category tree to GoodsCloud
            Mage::log('Copy the category tree to GoodsCloud');
            $this->createGCCategoriesFromCategories();

            // create price list
            Mage::log('create price list');
            $this->createDefaultPriceList();

            // create vat rate
            Mage::log('create vat rate');
            $this->createDefaultVatRate();

            // create company products (if needed) for all products
            // create channel products for all store views
            Mage::log('create products');
            $this->createProducts();

        } catch (Mage_Core_Exception $e) {
            if(isset($emulation) && isset($initialEnvironmentInfo)) {
                $emulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            }
            throw $e;
        }
        $emulation->stopEnvironmentEmulation($initialEnvironmentInfo);
    }

    /**
     * @return boolean
     */
    private function createProducts()
    {
        /* @var $stores Mage_Core_Model_Store[] */
        $stores = Mage::app()->getStores(true);

        // make sure admin store is first
        ksort($stores);

        return Mage::getModel('goodscloud_sync/firstWrite_products')
            ->setApi($this->api)
            ->createProducts($stores);
    }

    /**
     * create one default price list
     *
     * @return int
     */
    private function createDefaultPriceList()
    {
        return Mage::getModel('goodscloud_sync/firstWrite_priceList')
            ->setApi($this->api)
            ->createAndSaveDefaultPriceList();
    }

    /**
     * create one default vat rate
     *
     * @return int
     */
    private function createDefaultVatRate()
    {
        return Mage::getModel('goodscloud_sync/firstWrite_vatRate')
            ->setApi($this->api)
            ->createAndSaveDefaultVatList();
    }

    /**
     * create all channels in goodscloud from storeview data
     *
     * @return bool
     */
    private function createChannelsFromStoreView()
    {
        /* @var $stores Mage_Core_Model_Store[] */
        $stores = Mage::app()->getStores();

        Mage::getModel('goodscloud_sync/firstWrite_channels')
            ->setApi($this->api)
            ->createChannelsFromStoreviews($stores);
    }

    private function createPropertySetsFromAttributeSets()
    {
        $stores = Mage::app()->getStores();
        $productEntityId = Mage::getModel('eav/entity_type')->loadByCode('catalog_product')->getId();

        /* @var $attributeSets Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection */
        $attributeSets = Mage::getResourceModel('eav/entity_attribute_set_collection')
            ->addFieldToFilter('entity_type_id', $productEntityId);

        Mage::getModel('goodscloud_sync/firstWrite_propertySets')
            ->setApi($this->api)
            ->createPropertySetsFromAttributeSets($attributeSets, $stores);
    }

    private function createPropertySchemasFromAttributes()
    {
        $ignoredAttributes = Mage::helper('goodscloud_sync/api')->getIgnoredAttributes();
        /** @var $attributes Mage_Eav_Model_Resource_Entity_Attribute_Collection */
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addFieldToFilter('attribute_code', array('nin' => $ignoredAttributes));

        $stores = Mage::app()->getStores();

        Mage::getModel('goodscloud_sync/firstWrite_propertySchemas')
            ->setApi($this->api)
            ->createPropertySchemasFromAttributes($attributes, $stores);
    }

    private function mapPropertySchemasToPropertySets()
    {
        $propertySchemaMapper = Mage::getModel('goodscloud_sync/firstWrite_propertySchema2PropertySetMapper');

        $productEntityId = Mage::getModel('eav/entity_type')->loadByCode('catalog_product')->getId();
        /* @var $attributeSets Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection */
        $attributeSets = Mage::getResourceModel('eav/entity_attribute_set_collection')
            ->addFieldToFilter('entity_type_id', $productEntityId);

        $stores = Mage::app()->getStores();

        $propertySchemaMapper->mapProperty2PropertySets($attributeSets, $stores);
    }

    private function createGCCategoriesFromCategories()
    {
        $stores = Mage::app()->getStores();

        Mage::getModel('goodscloud_sync/firstWrite_categories')
            ->setApi($this->api)
            ->createCategories($stores);
    }

    private function checkInstalled()
    {
        if (!Mage::helper('goodscloud_sync/api')->getIdentifierAttribute()) {
            throw new GoodsCloud_Sync_Model_Exception_MissingConfigurationException(
                'Please configure identifier attribute.'
            );
        }

        if (!Mage::helper('goodscloud_sync/api')->getIdentifierType()) {
            throw new GoodsCloud_Sync_Model_Exception_MissingConfigurationException(
                'Please configure identifier type.'
            );
        }
    }

    private function getAndSaveCompanyId()
    {
        if (!Mage::helper('goodscloud_sync/api')->getCompanyId()) {
            /* @var $company GoodsCloud_Sync_Model_Api_Company */
            $company = Mage::getModel('goodscloud_sync/firstWrite_company')
                ->setApi($this->api)
                ->getCompany();

            Mage::helper('goodscloud_sync/api')->setCompanyId($company->getId());
        }
    }

    private function initApi()
    {
        $this->api = Mage::getModel('goodscloud_sync/api');
    }

    private function saveUpdateDateTime() {
        $updateTime = Mage::getModel('goodscloud_sync/sync_updateDateTime')
            ->loadSelf();

        $updateTime->setFlagData(now());

        $updateTime->save();

    }
}
