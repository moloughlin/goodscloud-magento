<?php

class GoodsCloud_Sync_Model_FirstWrite_PropertySets extends GoodsCloud_Sync_Model_FirstWrite_Base
{
    /**
     * @param Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection $attributeSets
     * @param Mage_Core_Model_Store[]                                 $storeViews
     *
     * @throws Mage_Core_Exception
     */
    public function createPropertySetsFromAttributeSets(
        Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection $attributeSets, array $storeViews
    ) {
        foreach ($storeViews as $view) {
            foreach ($attributeSets as $attributeSet) {
                $propertySetIds = json_decode($attributeSet->getPropertySetIds(), true);
                if (!isset($propertySetIds[$view->getId()])) {
                    $propertySetData = $this->createPropertySetFromAttributeSets($attributeSet, $view);
                    if (!$propertySetData) {
                        Mage::throwException('Error while creating property set');
                    }
                    $propertySetIds[$view->getId()] = $propertySetData->id;
                    $attributeSet->setPropertySetIds(json_encode($propertySetIds));
                    $attributeSet->save();
                }
            }
        }

    }

    private function createPropertySetFromAttributeSets(
        Mage_Eav_Model_Entity_Attribute_Set $set,
        Mage_Core_Model_Store $view
    ) {
        return $this->getApi()->createPropertySet($set, $view);
    }
}