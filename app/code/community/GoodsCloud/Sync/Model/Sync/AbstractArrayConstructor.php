<?php

class GoodsCloud_Sync_Model_Sync_AbstractArrayConstructor
{
    /**
     * @var Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection
     */
    protected $attributeSetCache;

    /**
     * @var Mage_Core_Model_Store[]
     */
    protected $storeViewCache;

    /**
     * @var Mage_Eav_Model_Entity_Attribute[]
     */
    protected $attributeCache;


    /**
     * @param Mage_Eav_Model_Entity_Attribute[] $attributes
     *
     * @return $this
     */
    public function setAttributeCache(array $attributes)
    {
        $this->attributeCache = $attributes;
        return $this;
    }

    /**
     * @param Mage_Eav_Model_Resource_Entity_Attribute_Set[] $attributeSets
     *
     * @return $this
     */
    public function setAttributeSetCache(array $attributeSets)
    {
        $this->attributeSetCache = $attributeSets;
        return $this;
    }

    /**
     * @param Mage_Core_Model_Store[] $storeViews
     *
     * @return $this
     */
    public function setStoreViewCache(array $storeViews)
    {
        foreach ($storeViews as $view) {
            $this->storeViewCache[$view->getGcChannelId()] = $view;
        }

        return $this;
    }

    /**
     * @param int $propertySetId
     *
     * @return string
     */
    protected function getAttributeSetNameFromPropertySetId($propertySetId)
    {
        /** @var $attributeSet Mage_Eav_Model_Entity_Attribute_Set */
        $attributeSet = $this->attributeSetCache[$propertySetId];
        return $attributeSet->getAttributeSetName();
    }

    /**
     * @param $channelId
     *
     * @return string
     */
    protected function getWebsiteByChannelId($channelId)
    {
        return $this->storeViewCache[$channelId]->getWebsite()->getCode();
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return bool|string
     */
    protected function getPropertyValue($name, $value)
    {
        $helper = Mage::helper('goodscloud_sync/api');
        $gcPropertyType = $helper->getPropertySchemaTypeForAttribute(
            $this->attributeCache[$name]
        );

        switch ($gcPropertyType) {
            case 'bool':
                return $value == 'Yes' ? true : false;
                break;
            case 'enum':
                return trim($value);
                break;
            case 'datetime':
                return $value;
                break;
            case 'free':
                return trim($value);
                break;
        }
        throw new LogicException(
            sprintf(
                'New type "%s", not implemented yet',
                $gcPropertyType
            )
        );
    }

}
