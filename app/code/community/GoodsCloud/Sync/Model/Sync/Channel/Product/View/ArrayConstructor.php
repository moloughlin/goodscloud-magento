<?php

class GoodsCloud_Sync_Model_Sync_Channel_Product_View_ArrayConstructor
    extends GoodsCloud_Sync_Model_Sync_AbstractArrayConstructor
{

    /**
     * @var GoodsCloud_Sync_Model_Api
     */
    private $api;

    /**
     * @var string[]
     */
    private $categoryCache;

    /**
     *
     * @param GoodsCloud_Sync_Model_Api_Channel_Product_View_Collection $products
     *
     * @return array
     */
    public function construct(
        GoodsCloud_Sync_Model_Api_Channel_Product_View_Collection $products
    ) {
        $importArray = array();
        foreach ($products as $product) {
            try {

                $importArray[$this->getSku($product)][]
                    = $this->buildProductArray($product);

            } catch (Exception $e) {
                Mage::logException($e);
                continue;
            }
        }

        return $importArray;
    }

    /**
     * @param GoodsCloud_Sync_Model_Api $api
     *
     * @return $this
     */
    public function setApi(GoodsCloud_Sync_Model_Api $api)
    {
        $this->api = $api;

        return $this;
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Channel_Product_View $product
     *
     * @return array
     */
    private function buildProductArray(
        GoodsCloud_Sync_Model_Api_Channel_Product_View $product
    ) {

        $importArray = array_merge(
            $this->buildPropertyKeys($product),
            $this->buildSpecialKeys($product),
            $this->buildRelations($product),
            $this->buildSeoData($product),
            $this->buildConfigurableAttributes($product),
            $this->buildImageKeys($product)
        );

        return $importArray;
    }

    /**
     * @return array
     */
    public function buildConfigurableAttributes(
        GoodsCloud_Sync_Model_Api_Channel_Product_View $productView
    ) {
        $channelProducts = $this->api->getChannelProductsForChannelView(
            $productView
        );

        $attributes = $this->guessConfigurableAttributes(
            $productView, $channelProducts
        );

        $configurableSettings = array(
            '_super_products_sku'   => $channelProducts->getColumnValues('sku'),
            '_super_attribute_code' => $attributes,
        );

        foreach ($channelProducts as $product) {
            /* @var $product GoodsCloud_Sync_Model_Api_Channel_Product */
            foreach ($attributes as $attribute) {
                $properties = $product->getProperties();
                $configurableSettings = array();
            }
        }

        return $configurableSettings;
    }

    /**
     * we want all attributes which are unser in the view and set in every product
     *
     * @param GoodsCloud_Sync_Model_Api_Channel_Product_View       $view
     * @param GoodsCloud_Sync_Model_Api_Channel_Product_Collection $products
     *
     * @return array
     */
    private function guessConfigurableAttributes(
        GoodsCloud_Sync_Model_Api_Channel_Product_View $view,
        GoodsCloud_Sync_Model_Api_Channel_Product_Collection $products
    ) {
        $notSetInView = array();
        foreach ($view->getProperties() as $key => $value) {
            if (empty($value)) {
                $notSetInView[$key] = $key;
            }
        }

        $notSetInProduct = array();
        $setInProduct = array();
        foreach ($products as $product) {
            /* @var $product GoodsCloud_Sync_Model_Api_Channel_Product */
            foreach ($product->getProperties() as $key => $value) {
                if (!empty($value)) {
                    $setInProduct[$key] = $key;
                } else {
                    $notSetInProduct[$key] = $key;
                }
            }
        }

        // remove all notSetInProduct from SetInProduct
        foreach ($notSetInProduct as $key) {
            unset($setInProduct[$key]);
        }

        // dow diff setInProduct with notSetInView
        $attributes = array_intersect($setInProduct, $notSetInView);

        $apiHelper = Mage::helper('goodscloud_sync/api_import');
        $unconfigurableAttributes = $apiHelper->getUnConfigurableAttributes();

        $attributes = array_diff($attributes, $unconfigurableAttributes);

        return $attributes;

    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Channel_Product_View $product
     *
     * @return array
     */
    private function buildPropertyKeys(
        GoodsCloud_Sync_Model_Api_Channel_Product_View $product
    ) {

        // "special" things
        $importArray = array(
            'sku' => $this->getSku($product),
        );

        foreach ($product->getProperties() as $propertyName => $propertyValue) {
            $importArray[$propertyName] = $this->getPropertyValue($propertyName,
                $propertyValue);
        }

        $description = $product->getChosenDescription();

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
            // TODO
        );
    }


    private function buildSeoData(
        GoodsCloud_Sync_Model_Api_Channel_Product_View $product
    ) {
        $seo = $product->getSeo();

        return array(
            //            'meta_title',
            'meta_keyword'     => implode(',', $seo['meta_keyword']),
            'meta_description' => $seo['meta_description'],
            //            'meta_autogenerate',
        );
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Channel_Product_View $product
     *
     * @return array
     */
    private function buildSpecialKeys(
        GoodsCloud_Sync_Model_Api_Channel_Product_View $product
    ) {
        return array(
            '_type'          => 'simple',
            '_attribute_set' => $this->getAttributeSetForProduct($product),
            '_category'      => $this->getCategories($product),
            '_store'         => $this->getStoreForProduct($product),
        );
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Channel_Product_View $product
     *
     * @return string[]
     */
    private function getCategories(
        GoodsCloud_Sync_Model_Api_Channel_Product_View $product
    ) {
        $categories = array();
        foreach ($product->getCategories() as $category) {
            $categories[] = $this->getCategoryPath(
                $category['external_identifier']
            );
        }

        return $categories;
    }

    /**
     * @param int $id
     *
     * @return string
     */
    private function getCategoryPath(
        $id
    ) {
        return $this->categoryCache[$id];
    }

    /**
     * @param string[] $categories
     *
     * @return GoodsCloud_Sync_Model_Sync_Channel_Product_View_ArrayConstructor
     */
    public
    function setCategoryCache(
        $categories
    ) {
        $this->categoryCache = $categories;

        return $this;
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Channel_Product_View $product
     *
     * @return array
     */
    public
    function buildRelations(
        GoodsCloud_Sync_Model_Api_Channel_Product_View $product
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
     * @param GoodsCloud_Sync_Model_Api_Channel_Product_View $channelProduct
     *
     * @return string
     */
    private function getAttributeSetForProduct(
        GoodsCloud_Sync_Model_Api_Channel_Product_View $channelProduct
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

    private function getSku(
        GoodsCloud_Sync_Model_Api_Channel_Product_View $product
    ) {
        return $product->getSku();
    }

    /**
     * @param GoodsCloud_Sync_Model_Api_Channel_Product_View $product
     *
     * @return int
     */
    private function getProductStatus(
        GoodsCloud_Sync_Model_Api_Channel_Product_View $product
    ) {
        if ($product->getActive()) {
            return Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
        }

        return Mage_Catalog_Model_Product_Status::STATUS_DISABLED;
    }

    private function getStoreForProduct(
        GoodsCloud_Sync_Model_Api_Channel_Product_View $product
    ) {
        return $this->storeViewCache[$product->getChannelId()]->getCode();
    }
}
