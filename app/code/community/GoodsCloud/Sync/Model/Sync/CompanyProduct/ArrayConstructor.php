<?php

class GoodsCloud_Sync_Model_Sync_CompanyProduct_ArrayConstructor
    extends GoodsCloud_Sync_Model_Sync_AbstractArrayConstructor
{

    /**
     * @param GoodsCloud_Sync_Model_Api_Company_Product_Collection $products
     *
     * @return array
     */
    public function construct(
        GoodsCloud_Sync_Model_Api_Company_Product_Collection $products
    ) {
        $importArray = array();
        foreach ($products as $product) {
            $importArray[$this->getSku($product)][]
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
            'sku'  => $this->getSku($product),
            'name' => $product->getLabel(),
        );

        foreach ($product->getProperties() as $propertyName => $propertyValue) {
            $importArray[$propertyName] = $this->getPropertyValue($propertyName,
                $propertyValue);
        }

        $availableDescription = $product->getAvailableDescriptions();
        $firstDescription = reset($availableDescription);
        return $importArray + array(

            'name'              => $product->getLabel(),
            'price'             => $helper->getPriceForCompanyProduct($product),
            'description'       => $firstDescription['long_description'],
            'short_description' => $firstDescription['short_description'],
            'weight'            => 0, // TODO write and get from goodscloud
            'status'            => $this->getProductStatus($product),
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
        // TODO supported by API but not yet used
        return array();
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
     * @param GoodsCloud_Sync_Model_Api_Company_Product $product
     *
     * @return string
     */
    private function getAttributeSetForProduct(
        GoodsCloud_Sync_Model_Api_Company_Product $product
    ) {
        $channelProduct = $this->getAnyChannelProduct($product);
        if ($channelProduct) {
            return $this->getAttributeSetNameFromPropertySetId(
                $channelProduct['property_set_id']
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
     * @param GoodsCloud_Sync_Model_Api_Company_Product $product
     *
     * @return string[]
     */
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
     * @param GoodsCloud_Sync_Model_Api_Company_Product $product
     *
     * @return string
     */
    private function getSku(GoodsCloud_Sync_Model_Api_Company_Product $product)
    {
        $channelProduct = $this->getAnyChannelProduct($product);
        if ($channelProduct) {
            return $channelProduct['sku'];
        }
        throw new RuntimeException(
            'No channel product found, import not possible'
        );
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Company_Product $product
     *
     * @return GoodsCloud_Sync_Model_Api_Channel_Product
     */
    private function getAnyChannelProduct(
        GoodsCloud_Sync_Model_Api_Company_Product $product
    ) {
        $channelProducts = $product->getChannelProducts();
        $channelProduct = reset($channelProducts);
        return $channelProduct;
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Company_Product $product
     *
     * @return int
     */
    private function getProductStatus(
        GoodsCloud_Sync_Model_Api_Company_Product $product
    ) {
        if ($product->getActive()) {
            return Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
        }
        return Mage_Catalog_Model_Product_Status::STATUS_DISABLED;
    }
}
