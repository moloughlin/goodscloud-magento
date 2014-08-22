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
        $this->checkInstalled();

        $this->api = Mage::getModel('goodscloud_sync/api');
        $this->getAndSaveCompanyId();

        // Add a Channel for every StoreView
        $this->createChannelsFromStoreView();

        // Add every AttributeSet as PropertySet to every Channel
        $this->createPropertySetsFromAttributeSets();

        // Add every Attribute as PropertySchema to every PropertySet
        $this->createPropertySchemasFromAttributes();

        // Map all PropertySchemas to the corresponding PropertySets
        $this->mapPropertySchemasToPropertySets();

        // Copy the category tree to GoodsCloud
        $this->createGCCategoriesFromCategories();
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

    // TODO make private
    public function getAndSaveCompanyId()
    {
        if (!Mage::helper('goodscloud_sync/api')->getCompanyId()) {
            /* @var $company GoodsCloud_Sync_Model_Api_Company */
            $company = Mage::getModel('goodscloud_sync/firstWrite_company')
                ->setApi($this->api)
                ->getCompany();

            Mage::helper('goodscloud_sync/api')->setCompanyId($company->getId());
        }
    }

}
