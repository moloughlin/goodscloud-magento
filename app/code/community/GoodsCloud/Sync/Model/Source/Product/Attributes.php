<?php

class GoodsCloud_Sync_Model_Source_Product_Attributes extends Mage_Eav_Model_Resource_Entity_Attribute_Collection
{
    protected $_options;


    public function toOptionArray()
    {
        if (!$this->_options) {
            $productEntityType = Mage::getModel('eav/entity_type')->loadByCode('catalog_product');
            $this->setEntityTypeFilter($productEntityType->getId());
            $this->addFieldToFilter('is_visible', 1);
            $this->_options = parent::_toOptionArray('attribute_id', 'attribute_code');
        }
        return $this->_options;
    }
}