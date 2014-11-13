<?php

class GoodsCloud_Sync_Model_Sync_Company_Product_View_ArrayConstructor
    extends GoodsCloud_Sync_Model_Sync_AbstractArrayConstructor
{

    /**
     * @param GoodsCloud_Sync_Model_Api_Company_Product_View_Collection $products
     *
     * @return array
     */
    public function construct(
        GoodsCloud_Sync_Model_Api_Company_Product_View_Collection $products
    ) {
        $importArray = array();
        foreach ($products as $product) {
            $importArray[$this->getSku($product)][]
                = $this->buildProductArray($product);
        }

        return $importArray;

    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Company_Product_View $product
     *
     * @return array
     */
    private function buildProductArray(
        GoodsCloud_Sync_Model_Api_Company_Product_View $product
    ) {

        $importArray = array_merge(
            $this->buildPropertyKeys($product),
            $this->buildSpecialKeys($product),
            $this->buildRelations($product)
        );

        return $importArray;
    }

    /**
     * goodscloud company prodoct views don't have properties
     *
     * so not much to do here
     *
     * @param GoodsCloud_Sync_Model_Api_Company_Product_View $product
     *
     * @return array
     */
    private function buildPropertyKeys(
        GoodsCloud_Sync_Model_Api_Company_Product_View $product
    ) {

        // "special" things
        $importArray = array(
            'sku'  => $this->getSku($product),
            'name' => $product->getLabel(),
        );

        $availableDescription = $product->getAvailableDescriptions();
        $firstDescription = reset($availableDescription);
        return $importArray + array(

            'name'              => $product->getLabel(),
            // we set the prices on the channel views
            'price'             => 999999,
            'description'       => $firstDescription['long_description'],
            'short_description' => $firstDescription['short_description'],
            'weight'            => 0,
            'status'            => Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
            'visibility'        => 4,
            'tax_class_id'      => 2,
        );
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Company_Product_View $product
     *
     * @return array
     */
    private function buildSpecialKeys(
        GoodsCloud_Sync_Model_Api_Company_Product_View $product
    ) {
        return array(
            '_type'             => 'configurable',
            '_attribute_set'    => $this->getAttributeSetForProduct($product),
            '_product_websites' => $this->getWebsites($product),
        );
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Company_Product_View $product
     *
     * @return array
     */
    public function buildRelations(
        GoodsCloud_Sync_Model_Api_Company_Product_View $product
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
     * @param GoodsCloud_Sync_Model_Api_Company_Product_View $product
     *
     * @return string
     */
    private function getAttributeSetForProduct(
        GoodsCloud_Sync_Model_Api_Company_Product_View $product
    ) {
        $channelProduct = $this->getAnyChannelProductView($product);
        if ($channelProduct) {
            return $this->getAttributeSetNameFromPropertySetId(
                $channelProduct['property_set_id']
            );
        }
        throw new RuntimeException(
            sprintf(
                'Product "%s" can not be imported due to missing Channel Product',
                $product->getLabel()
            )
        );
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Company_Product_View $product
     *
     * @return string[]
     */
    private function getWebsites(
        GoodsCloud_Sync_Model_Api_Company_Product_View $product
    ) {
        $websiteCodes = array();
        foreach ($product->getChannelProductViews() as $channelProductView) {
            $websiteCodes[] = $this->getWebsiteByChannelId(
                $channelProductView['channel_id']
            );
        }

        return array_unique($websiteCodes);
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Company_Product_View $product
     *
     * @return string
     */
    private function getSku(
        GoodsCloud_Sync_Model_Api_Company_Product_View $product
    ) {
        $channelProductView = $this->getAnyChannelProductView($product);
        if ($channelProductView) {
            return $channelProductView['sku'];
        }
        throw new RuntimeException(
            'No channel product view found, import not possible'
        );
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Company_Product_View $product
     *
     * @return GoodsCloud_Sync_Model_Api_Channel_Product_View
     */
    private function getAnyChannelProductView(
        GoodsCloud_Sync_Model_Api_Company_Product_View $product
    ) {
        $channelProducts = $product->getChannelProductViews();
        $channelProduct = reset($channelProducts);
        return $channelProduct;
    }

}
