<?php

class GoodsCloud_Sync_Helper_Api extends Mage_Core_Helper_Abstract
{
    const XML_CONFIG_IDENTIFIER_TYPE = 'goodscloud_sync/shop/identifier_type';
    const XML_CONFIG_IDENTIFIER_ATTRIBUTE = 'goodscloud_sync/shop/identifier_attribute';
    const XML_CONFIG_BASE_URL = 'goodscloud_sync/advanced/base_url';
    const XML_CONFIG_EMAIL = 'goodscloud_sync/basic/username';
    const XML_CONFIG_PASSWORD = 'goodscloud_sync/basic/password';
    const XML_CONFIG_IGNORED_ATTRIBUTES = 'goodscloud_sync/api/ignored_attributes';
    const XML_CONFIG_BOOLEAN_SOURCE_MODELS = 'goodscloud_sync/api/boolean_source_models';
    const XML_CONFIG_ENUM_TYPES = 'goodscloud_sync/api/enum_types';

    const XML_CONFIG_COMPANY_ID = 'goodscloud_sync/api/company_id';
    const XML_CONFIG_DEFAULT_PRICE_LIST_ID = 'goodscloud_sync/api/default_price_list_id';
    const XML_CONFIG_DEFAULT_VAT_RATE_ID = 'goodscloud_sync/api/default_vat_rate_id';

    /**
     * get the baseuri for api requests
     *
     * @return string
     */
    public function getUri()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_BASE_URL);
    }

    /**
     * email address to log into goodscloud api
     *
     * @return string
     */
    public function getEmail()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_EMAIL);
    }

    /**
     * password to log into goodscloud api
     *
     * @return string
     */
    public function getPassword()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_PASSWORD);
    }

    public function getIgnoredAttributes()
    {
        return array_keys(Mage::getStoreConfig(self::XML_CONFIG_IGNORED_ATTRIBUTES));
    }

    public function getBooleanSourceModels()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_BOOLEAN_SOURCE_MODELS);
    }

    public function getEnumTypes()
    {
        return array_keys(Mage::getStoreConfig(self::XML_CONFIG_ENUM_TYPES));
    }

    /**
     * determine type in goodscloud
     *
     * goodscloud knows types:
     * - free
     * - enum (select, multiselect)
     * - range (doesn't exist in magento)
     * - bool (depends on source_model = boolean, yes_no)
     * - datetime
     *
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     *
     * @return string
     */
    public function getPropertySchemaTypeForAttribute(Mage_Eav_Model_Entity_Attribute $attribute)
    {
        if (in_array($attribute->getFrontendInput(), array('select', 'multiselect'))) {
            // it is enum or bool
            if (in_array($attribute->getSourceModel(), array('eav/entity_attribute_source_boolean'))) {
                return 'bool';
            } else {
                return 'enum';
            }
        }

        if ($attribute->getBackendType() == 'datetime') {
            return 'datetime';
        }
        return 'free';
    }

    /**
     * @param Mage_Eav_Model_Entity_Attribute $attribute attribute to get options for
     * @param Mage_Core_Model_Store           $view      view to get translated options for
     *
     * @return array options for attribute
     * @throws Mage_Core_Exception
     */
    public function getPropertySchemaValuesForAttribute(
        Mage_Eav_Model_Entity_Attribute $attribute, Mage_Core_Model_Store $view
    ) {
        try {
            $values = array();
            foreach ($attribute->getSource()->getAllOptions() as $option) {
                $values[] = $option['value'];
            }
            return $values;
        } catch (Mage_Core_Exception $e) {
            $sourceModelNotFound = 'Source model "" not found for attribute ';
            $length = strlen($sourceModelNotFound);
            if (substr($e->getMessage(), 0, $length) == $sourceModelNotFound) {
                return array();
            }
            throw $e;
        }
    }

    public function isAttributeMultiValue(Mage_Eav_Model_Entity_Attribute $attribute)
    {
        return $attribute->getFrontendInput() == 'multiselect';
    }

    public function getIdentifierType()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_IDENTIFIER_TYPE);
    }

    public function getIdentifierAttribute()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_IDENTIFIER_ATTRIBUTE);
    }

    public function createDescriptions(Mage_Catalog_Model_Product $product)
    {
        /** @var $apiHelper GoodsCloud_Sync_Helper_Api */
        $apiHelper = Mage::helper('goodscloud_sync/api');
        $descriptions
            = array(
            array(
                //    id	column	Integer	not NULL Primary key.
                //    chosen_channel_product_views	relationship	List of ChannelProductView entries.
                //    chosen_channel_products	relationship	List of ChannelProduct entries.
                //    company_product_views	relationship	List of CompanyProductView entries.
                //    company_products	relationship	List of CompanyProduct entries.
                //    label	column	String 256 characters or less.
                'label'             => $product->getStore()->getName(),
                //    language_code	column	LowercaseEnum	not NULL The language for this description. Must be ISO-639 codes
                // TODO get the language somewhere
                'language_code'     => 'en',
                //    long_description	column	Text Any length allowed.
                'long_description'  => $product->getDescription(),
                //    rights	column	String 10 characters or less. the rights to the description, might change to an enum
                //    short_description	column	Text Any length allowed.
                'short_description' => $product->getShortDescription(),
                //    updated	column	DateTime	not NULL ISO format datetime with timezone offset: 1997-07-16T19:20:30.45+01:00. The time when this row was last updated. Read-only.
                //    version	column	Integer	not NULL	1 Current version number of this entry, incremented each time it is changed. Read-only.
                //    company_id	column	Integer	not NULL ForeignKey('company.id') ON DELETE CASCADE
                'company_id'        => $apiHelper->getCompanyId(),
                //    company	relationship	Single Company entry.
                //    created	hybrid_property The time when this row was created. Determin  ed by looking in the history for this table. Read-only.
            )
        );

        return $descriptions;
    }
    /**
     * get the company if from goodscloud
     *
     * @return int
     */
    public function getCompanyId()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_COMPANY_ID);
    }

    /**
     * save the company id from goodscloud in the config and refresh the cache
     *
     * reinit the config, so the value from DB is available in this request
     * and the cache is refreshed
     *
     * @param int $companyId
     */
    public function setCompanyId($companyId)
    {
        $config = Mage::app()->getConfig();
        $config->saveConfig(self::XML_CONFIG_COMPANY_ID, $companyId);
        $config->reinit();
        $config->saveCache();
    }

    public function getDefaultPriceListId()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_DEFAULT_PRICE_LIST_ID);
    }

    public function setDefaultPriceListId($priceListId)
    {
        $config = Mage::app()->getConfig();
        $config->saveConfig(self::XML_CONFIG_DEFAULT_PRICE_LIST_ID, $priceListId);
        $config->reinit();
        $config->saveCache();
    }

    public function getDefaultVatRate()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_DEFAULT_VAT_RATE_ID);
    }

    public function setDefaultVatRate($vatRate)
    {
        $config = Mage::app()->getConfig();
        $config->saveConfig(self::XML_CONFIG_DEFAULT_VAT_RATE_ID, $vatRate);
        $config->reinit();
        $config->saveCache();
    }
}
