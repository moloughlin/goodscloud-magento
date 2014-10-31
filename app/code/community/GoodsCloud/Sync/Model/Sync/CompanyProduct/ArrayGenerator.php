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

    public function construct(array $products)
    {
        foreach ($products as $product) {
            $this->importArray[$product->getSku()][]
                = $this->getProductArray($product);
        }

    }

    private function getProductArray($product)
    {
        $importArray = array(
            'sku'               => $product->getSku(),

            // TODO hardcoded, there is no other type yet
            '_type'             => 'simple',
            '_attribute_set'    => $this->getAttributeSetForProduct($product),
            '_product_websites' => 'base', // TODO not yet implemented
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
            'qty'               => 76,
        );

        return $importArray;
    }

    public function setAttributeSetCache(
        Mage_Eav_Model_Resource_Entity_Attribute_Set_Collection $attributeSetCollection
    ) {
        $this->attributeSetCache = $attributeSetCollection;
    }
}
