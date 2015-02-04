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
        if ($attributeSet === null) {
            throw new RuntimeException('Product has no property set.');
        }

        return $attributeSet->getAttributeSetName();
    }

    /**
     * @param $channelId
     *
     * @throws Exception
     * @return string
     */
    protected function getWebsiteByChannelId($channelId)
    {
        if ($this->storeViewCache[$channelId] === null) {
            return $this->storeViewCache[$channelId]->getWebsite()->getCode();
        }
        throw new Exception('Channel not found, either inventory channel or not magento related channel');
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return bool|string
     */
    protected function getPropertyValue($name, $value)
    {
        if (!isset($this->attributeCache[$name])) {
            return $value;
        }
        $attribute = $this->attributeCache[$name];
        $helper = Mage::helper('goodscloud_sync/api');
        $gcPropertyType = $helper->getPropertySchemaTypeForAttribute(
            $attribute
        );

        switch ($gcPropertyType) {
            case 'bool':
                return $value == 'Yes' ? true : false;
                break;
            case 'enum':
                return $this->getAttributeValue($value, $attribute);
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

    /**
     * @param string                              $value
     * @param Mage_Catalog_Model_Entity_Attribute $attribute
     *
     * @return string
     */
    protected function getAttributeValue($value, $attribute)
    {
        if ($attribute instanceof Mage_Catalog_Model_Resource_Eav_Attribute
            && $attribute->getAttributeCode() == 'visibility'
        ) {
            return $attribute->getSource()->getOptionId($value);
        }

        return trim($value);
    }


    /**
     * @param GoodsCloud_Sync_Model_Api_Channel_Product_View $product
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    protected function buildImageKeys($product)
    {
        $mediaGalleryAttribute = Mage::getModel('eav/entity_attribute')
            ->loadByCode(
                Mage_Catalog_Model_Product::ENTITY,
                'media_gallery'
            );

        $_media_image = array();
        $_media_attribute_id = array();
        $_media_is_disabled = array();
        $_media_lable = array();
        $imagePropertyName = $this->getImagePropertyName($product);
        $images = $product->getDataUsingMethod($imagePropertyName);
        foreach ($images as $image) {
            $_media_image[] = $this->getLink($image['url_fragment']);
            $_media_attribute_id[] = $mediaGalleryAttribute->getId();
            $_media_is_disabled[] = false;
            $_media_lable[] = $image['alt_text'];
        }

        return array(
            '_media_image'        => $_media_image,
            '_media_attribute_id' => $_media_attribute_id,
            '_media_is_disabled'  => $_media_is_disabled,
            '_media_lable'        => $_media_lable,
        );
    }

    private function getImagePropertyName($product)
    {
        switch (get_class($product)) {
            case 'GoodsCloud_Sync_Model_Api_Channel_Product':
                return 'chosen_images';
            case 'GoodsCloud_Sync_Model_Api_Channel_Product_View':
                return 'chosen_images';
            case 'GoodsCloud_Sync_Model_Api_Company_Product':
                return 'available_images';
            case 'GoodsCloud_Sync_Model_Api_Company_Product_View':
                return 'available_images';
        }
        throw new InvalidArgumentException('Argument must be a product type');
    }

    /**
     * @param string $image
     *
     * @return string
     */
    private function getLink($image)
    {
        return Mage::getSingleton('goodscloud_sync/sync_image_downloader')
            ->getLink($image);
    }
}
