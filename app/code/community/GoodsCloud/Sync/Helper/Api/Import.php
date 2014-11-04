<?php

class GoodsCloud_Sync_Helper_Api_Import extends Mage_Core_Helper_Abstract
{

    /**
     * @var Mage_Tax_Model_Config
     */
    private $taxConfig;

    function __construct()
    {
        $this->taxConfig = Mage::getModel('tax/config');
    }


    public function getPriceForCompanyProduct(
        GoodsCloud_Sync_Model_Api_Company_Product $product
    ) {
        foreach ($product->getPrices() as $price) {
            if ($price['minimum_quantity'] <= 1) {
                return $this->getPrice($price);
            }
        }
        throw new RuntimeException('Product doesn\'t have a valid default price');
    }

    private function getPrice(array $price)
    {
        if ($this->taxConfig->priceIncludesTax()) {
            return $price['gross'];
        } else {
            return $price['net'];
        }
    }
}
