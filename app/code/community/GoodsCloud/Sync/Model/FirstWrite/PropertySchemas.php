<?php

class GoodsCloud_Sync_Model_FirstWrite_PropertySchemas extends GoodsCloud_Sync_Model_FirstWrite_Base
{
    /**
     * @param Mage_Eav_Model_Resource_Entity_Attribute_Collection $attributes
     * @param Mage_Core_Model_Store[]                             $stores
     */
    public function createPropertySchemasFromAttributes(
        Mage_Eav_Model_Resource_Entity_Attribute_Collection $attributes, array $stores
    ) {
        foreach ($stores as $store) {
            if(!$store->getGcChannelId()) {
                Mage::throwException(sprintf('Store %s has no gc channel id set!', $store->getName()));
            }
            foreach ($attributes as $attribute) {
                $propertySchemaIds = json_decode($attribute->getGcPropertySchemaIds(), true);
                if (!isset($propertySchemaIds[$store->getGcChannelId()])) {
                    $propertySchemaData = $this->createPropertySchemaFromAttribute($attribute, $store);
                    if (!$propertySchemaData) {
                        Mage::throwException('Error while creating property schema');
                    }
                    $propertySchemaIds[$store->getGcChannelId()] = $propertySchemaData->id;
                    $attribute->setGcPropertySchemaIds(json_encode($propertySchemaIds));
                    $attribute->save();
                }
            }
        }
    }

    private function createPropertySchemaFromAttribute(
        Mage_Eav_Model_Entity_Attribute $attribute, Mage_Core_Model_Store $store
    ) {
        return $this->getApi()->createPropertySchema($attribute, $store);
    }
}
