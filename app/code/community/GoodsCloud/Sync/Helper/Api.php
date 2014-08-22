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
        Mage::getConfig()->saveConfig(self::XML_CONFIG_COMPANY_ID, $companyId);
        Mage::app()->getConfig()->reinit();
    public function getDefaultPriceList()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_DEFAULT_PRICE_LIST_ID);
    }

    public function setDefaultPriceList($priceListId)
    {
        $config = Mage::app()->getConfig();
        $config->saveConfig(self::XML_CONFIG_DEFAULT_PRICE_LIST_ID, $priceListId);
        $config->reinit();
        $config->saveCache();
    }
}
