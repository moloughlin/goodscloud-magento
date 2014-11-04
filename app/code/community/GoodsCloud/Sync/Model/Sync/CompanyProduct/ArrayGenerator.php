<?php

class GoodsCloud_Sync_Model_Sync_CompanyProduct_ArrayConstructor
{

    /**
     * @var Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection
     */
    private $attributeSetCache;

    /**
     * @var Mage_Core_Model_Store[]
     */
    private $storeViewCache;

    /**
     * @param GoodsCloud_Sync_Model_Api_Company_Product_Collection $products
     *
     * @return array
     */
    public function construct(
        GoodsCloud_Sync_Model_Api_Company_Product_Collection $products
    ) {
        foreach ($products as $product) {
            $importArray[$product->getSku()][]
                = $this->buildProductArray($product);
        }

        return $importArray;

    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Company_Product $product
     *
     * @return array
     */
    private function buildProductArray(
        GoodsCloud_Sync_Model_Api_Company_Product $product
    ) {

        $importArray = array_merge(
            $this->buildPropertyKeys($product),
            $this->buildSpecialKeys($product),
            $this->buildRelations($product)
        );

        return $importArray;
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Company_Product $product
     *
     * @return array
     */
    private function buildPropertyKeys(
        GoodsCloud_Sync_Model_Api_Company_Product $product
    ) {

        $helper = Mage::helper('goodscloud_sync/api_import');

        // "special" things
        $importArray = array(
            'sku'  => $product->getSku(),
            'name' => $product->getLabel(),
        );

        foreach ($product->getProperties() as $propertyName => $propertyValue) {
            $importArray[$propertyName] = $propertyValue;
        }

        return array(

            'name'              => $product->getLabel(),
            'price'             => $helper->getPriceForCompanyProduct($product),
            'description'       => reset($product->getAvailableDescriptions())
                ->getLongDescription(),
            'short_description' => reset($product->getAvailableDescriptions())
                ->getShortDescription(),
            'weight'            => 0, // TODO write and get from goodscloud
            'status'            => (int)$product->getActive(),
            'visibility'        => 4,
            'tax_class_id'      => 2,
            'manage_stock'      => $product->getStocked(),
            'qty'               => $product->getStockedQuantity(),
        );
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Company_Product $product
     *
     * @return array
     */
    private function buildSpecialKeys(
        GoodsCloud_Sync_Model_Api_Company_Product $product
    ) {
        return array(
            '_type'             => 'simple',
            '_attribute_set'    => $this->getAttributeSetForProduct($product),
            '_product_websites' => $this->getWebsites($product),
            // TODO not yet implemented
            '_category',
            '_media_image',
            '_media_attribute_id',
            '_media_is_disabled',
            '_media_position',
            '_media_lable',
            // TYPO in magento core, don't fix!
            '_store',
        );
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Company_Product $product
     *
     * @return array
     */
    public function buildRelations(
        GoodsCloud_Sync_Model_Api_Company_Product $product
    ) {
        return array(
            // Upsell
            '_links_upsell_sku',
            '_links_upsell_position',
            // Crossell
            '_links_crosssell_sku',
            '_links_crosssell_position',
            // Related
            '_links_related_sku',
            '_links_related_position',
        );

    }

    /**
     * @param Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection $attributeSetCollection
     *
     * @return $this
     */
    public function setAttributeSetCache(
        Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection $attributeSetCollection
    ) {
        $this->attributeSetCache = $attributeSetCollection;
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
     * @param GoodsCloud_Sync_Model_Api_Company_Product $product
     *
     * @return string
     */
    private function getAttributeSetForProduct(
        GoodsCloud_Sync_Model_Api_Company_Product $product
    ) {
        $channelProduct = reset($product->getChannelProducts());
        if ($channelProduct) {
            return $this->getAttributeSetNameFromId(
                $channelProduct['property_set']['external_identifier']
            );
        }
        throw new RuntimeException(
            sprintf(
                'Product "%s" can not be imported due to missing Channel Product',
                $product->getGtin()
            )
        );
    }

    /**
     * @param int $id
     *
     * @return string
     */
    private function getAttributeSetNameFromId($id)
    {
        /** @var $attributeSet Mage_Eav_Model_Entity_Attribute_Set */
        $attributeSet = $this->attributeSetCache->getItemById($id);
        return $attributeSet->getAttributeSetName();
    }

    private function getWebsites(
        GoodsCloud_Sync_Model_Api_Company_Product $product
    ) {
        $websiteCodes = array();
        foreach ($product->getChannelProducts() as $channelProduct) {
            $websiteCodes[]
                = $this->getWebsiteByChannelId($channelProduct['channel_id']);
        }

        return array_unique($websiteCodes);
    }

    /**
     * @param $channelId
     *
     * @return string
     */
    private function getWebsiteByChannelId($channelId)
    {
        return $this->storeViewCache[$channelId]->getWebsite()->getCode();
    }


}
