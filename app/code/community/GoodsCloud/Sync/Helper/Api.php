<?php

class GoodsCloud_Sync_Helper_Api extends Mage_Core_Helper_Abstract
{
    /**
     * get the baseuri for api requests
     *
     * @return string
     */
    public function getUri()
    {
        return Mage::getStoreConfig('goodscloud/advanced/base_url');
    }

    /**
     * email address to log into goodscloud api
     *
     * @return string
     */
    public function getEmail()
    {
        return Mage::getStoreConfig('goodscloud/basic/email');
    }

    /**
     * password to log into goodscloud api
     *
     * @return string
     */
    public function getPassword()
    {
        return Mage::getStoreConfig('goodscloud/basic/password');
    }

    public function getIgnoredAttributes()
    {
        return array_keys(Mage::getStoreConfig('goodscloud_sync/api/ignored_attributes'));
    }

    public function getBooleanSourceModels()
    {
        return Mage::getStoreConfig('goodscloud_sync/api/boolean_source_models');
    }

    public function getEnumTypes()
    {
        return array_keys(Mage::getStoreConfig('goodscloud_sync/api/enum_types'));
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
}
