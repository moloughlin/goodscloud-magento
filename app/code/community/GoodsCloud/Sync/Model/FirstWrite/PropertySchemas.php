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
            foreach ($attributes as $attribute) {
                $this->createPropertySchemaFromAttribute($attribute, $store);
            }
        }
    }

    private function createPropertySchemaFromAttribute(
        Mage_Eav_Model_Entity_Attribute $attribute, Mage_Core_Model_Store $store
    ) {
        $this->getApi()->createPropertySchema($attribute, $store);
    }
}