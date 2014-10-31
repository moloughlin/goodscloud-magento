<?php

class GoodsCloud_Sync_Model_Sync_CompanyProduct_ArrayConstructor
{
    /**
     * @var array
     */
    private $importArray;

    /**
     * @var Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection
     */
    private $attributeSetCache;

    /**
     * @param array $products
     */
    public function construct(array $products)
    {
        foreach ($products as $product) {
            $this->importArray[$product->getSku()][]
                = $this->buildProductArray($product);
        }

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

        // "special" things
        $importArray = array(
            'sku'  => $product->getSku(),
            'name' => $product->getLabel(),
        );

        foreach ($product->getProperties() as $propertyName => $propertyValue) {
            $importArray[$propertyName] = $propertyValue;
        }

        return array(

            'name'              => $product->getName(),
            'price'             => $product->getPrice(),
            'description'       => current($product->getAvailableDescriptions())
                ->getLongDescription(),
            'short_description' => current($product->getAvailableDescriptions())
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
            '_product_websites' => 'base', // TODO not yet implemented
            '_category',
            '_media_image',
            '_media_attribute_id',
            '_media_is_disabled',
            '_media_position',
            '_media_lable', // TYPO in magento core, don't fix!
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

    public function setAttributeSetCache(
        Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection $attributeSetCollection
    ) {
        $this->attributeSetCache = $attributeSetCollection;
    }
}
