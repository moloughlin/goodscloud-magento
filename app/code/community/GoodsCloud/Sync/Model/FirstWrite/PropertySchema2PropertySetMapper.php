<?php

class GoodsCloud_Sync_Model_FirstWrite_PropertySchema2PropertySetMapper extends GoodsCloud_Sync_Model_FirstWrite_Base
{
    /**
     * @param Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection $propertySets
     * @param Mage_Core_Model_Store[]                                 $views
     */
    public function mapProperty2PropertySets(
        Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection $propertySets, array $views
    ) {
        foreach ($views as $view) {
            $gcPropertySetIds = null;
            $requiredPropertySchemaIds = array();
            $optionalPropertySchemaIds = array();
            foreach ($propertySets as $set) {
                /** @var $attributes Mage_Eav_Model_Resource_Entity_Attribute_Collection */
                $attributes = Mage::getModel('catalog/product_attribute_api')->items($set->getId());
                $gcPropertySetIds = json_decode($set->getGcPropertySetIds(), true);

                if (!isset($gcPropertySetIds[$view->getId()])) {
                    // if the property set is not yet exported,
                    continue;
                }

                $requiredPropertySchemaIds[] = $gcPropertySetIds[$view->getId()];
                $attributeIds = array();
                foreach ($attributes as $attribute) {
                    if (in_array($attribute['code'], Mage::helper('goodscloud_sync/api')->getIgnoredAttributes())) {
                        continue;
                    }
                    $attributeIds[] = $attribute['attribute_id'];
                }

                $attributeCollection = Mage::getResourceModel('catalog/product_attribute_collection')
                    ->addFieldToFilter('main_table.attribute_id', array('in' => $attributeIds));

                foreach ($attributeCollection as $attribute) {
                    /** @var $attribute Mage_Eav_Model_Entity_Attribute */
                    $gcPropertySchemaIds = json_decode($attribute->getGcPropertySchemaIds(), true);

                    if (!isset($gcPropertySchemaIds[$view->getId()])) {
                        continue;
                    }

                    if ($attribute['required']) {
                        $requiredPropertySchemaIds[] = $gcPropertySchemaIds[$view->getId()];
                    } else {
                        $optionalPropertySchemaIds[] = $gcPropertySchemaIds[$view->getId()];
                    }
                }
            }
            if (isset($gcPropertySetIds) && isset($gcPropertySetIds[$view->getId()])) {
                if (!empty($optionalPropertySchemaIds) || !empty($requiredPropertySchemaIds)) {
                    $this->getApi()->mapPropertySchema2PropertySet(
                        $requiredPropertySchemaIds, $optionalPropertySchemaIds, $gcPropertySetIds[$view->getId()]
                    );
                }
            }
        }
    }
}
