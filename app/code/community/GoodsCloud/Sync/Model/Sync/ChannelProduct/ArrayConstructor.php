<?php

class GoodsCloud_Sync_Model_Sync_ChannelProduct_ArrayConstructor
    extends AbstractArrayConstructor
{

    /**
     * @param GoodsCloud_Sync_Model_Api_Channel_Product_Collection $products
     *
     * @return array
     */
    public function construct(
        GoodsCloud_Sync_Model_Api_Channel_Product_Collection $products
    ) {
        $importArray = array();
        foreach ($products as $product) {
            $importArray[$this->getSku($product)][]
                = $this->buildProductArray($product);
        }

        return $importArray;

    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Channel_Product $product
     *
     * @return array
     */
    private function buildProductArray(
        GoodsCloud_Sync_Model_Api_Channel_Product $product
    ) {

        $importArray = array_merge(
            $this->buildPropertyKeys($product),
            $this->buildSpecialKeys($product),
            $this->buildRelations($product)
        );

        return $importArray;
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Channel_Product $product
     *
     * @return array
     */
    private function buildPropertyKeys(
        GoodsCloud_Sync_Model_Api_Channel_Product $product
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

        $description = $product->getChosenDescription();
        $companyProduct = $product->getCompanyProduct();

        return $importArray + array(

            'name'              => $description['label'],
            // TODO implement if price is ot defined global
            //'price'             => $helper->getPriceForCompanyProduct($product),
            'description'       => $description['long_description'],
            'short_description' => $description['short_description'],
            // TODO write and get from goodscloud
            'weight'            => 0,
            'status'            => $this->getProductStatus($product),
            'visibility'        => 4,
            'tax_class_id'      => 2,
            'manage_stock'      => $companyProduct['stocked'],
            'qty'               => $product->getStockedQuantity(),
        );
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Channel_Product $product
     *
     * @return array
     */
    private function buildSpecialKeys(
        GoodsCloud_Sync_Model_Api_Channel_Product $product
    ) {
        return array(
            '_type'          => 'simple',
            '_attribute_set' => $this->getAttributeSetForProduct($product),
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
     * @param GoodsCloud_Sync_Model_Api_Channel_Product $product
     *
     * @return array
     */
    public function buildRelations(
        GoodsCloud_Sync_Model_Api_Channel_Product $product
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
     * @param GoodsCloud_Sync_Model_Api_Channel_Product $channelProduct
     *
     * @return string
     */
    private function getAttributeSetForProduct(
        GoodsCloud_Sync_Model_Api_Channel_Product $channelProduct
    ) {
        if ($channelProduct) {
            return $this->getAttributeSetNameFromPropertySetId(
                $channelProduct['property_set_id']
            );
        }
        throw new RuntimeException(
            sprintf(
                'Product "%s" can not be imported due to missing Channel Product',
                $channelProduct->getSku()
            )
        );
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Channel_Product $product
     *
     * @return string[]
     */
    private function getWebsites(
        GoodsCloud_Sync_Model_Api_Channel_Product $product
    ) {
        $websiteCodes = array();
        foreach ($product->getChannelProducts() as $channelProduct) {
            $websiteCodes[]
                = $this->getWebsiteByChannelId($channelProduct['channel_id']);
        }

        return array_unique($websiteCodes);
    }


    private function getSku(GoodsCloud_Sync_Model_Api_Channel_Product $product)
    {
        $channelProduct = $this->getAnyChannelProduct($product);
        if ($channelProduct) {
            return $channelProduct['sku'];
        }
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Channel_Product $product
     *
     * @return mixed
     */
    private function getAnyChannelProduct(
        GoodsCloud_Sync_Model_Api_Channel_Product $product
    ) {
        $channelProducts = $product->getChannelProducts();
        $channelProduct = reset($channelProducts);
        return $channelProduct;
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Channel_Product $product
     *
     * @return int
     */
    private function getProductStatus(
        GoodsCloud_Sync_Model_Api_Channel_Product $product
    ) {
        if ($product->getActive()) {
            return Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
        }
        return Mage_Catalog_Model_Product_Status::STATUS_DISABLED;
    }
}
