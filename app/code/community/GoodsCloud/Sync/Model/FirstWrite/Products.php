<?php

class GoodsCloud_Sync_Model_FirstWrite_Products extends GoodsCloud_Sync_Model_FirstWrite_Base
{
    const PAGE_SIZE = 100;

    /**
     * @var GoodsCloud_Sync_Model_FirstWrite_ProductList
     */
    private $productList;

    public function createProducts($views)
    {
        $this->productList = Mage::getModel('goodscloud_sync/firstWrite_productList');
        if (!$this->productList->isFinished()) {
            $this->prepareProductList();
            $this->createCompanyProducts();
            $this->createChannelProducts($views);
        }
    }

    /**
     * save a list of all products to be exported to know which already are exported
     */
    private function prepareProductList()
    {
        if (!$this->productList->isFilled()) {
            $collection = Mage::getResourceModel('catalog/product_collection');
            // only export physical items
            $collection->addFieldToFilter(
                'type_id', array(
                    'in' => array(
                        Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
                        Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
                        Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
                        Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
                    )
                )
            );
            $allIds = $collection->getAllIds();
            $this->log('Added all IDs to the queue: ' . implode(', ', $allIds));
            $this->productList->setProductList($allIds);
        }
    }

    /**
     * create for all products existing in the shop a company product (if not yet created)
     */
    private function createCompanyProducts()
    {
        /** @see http://magento.stackexchange.com/a/25908/217 */
        // It is intended to create two collections! Don't change this, because of a core bug!

        $adminStore = Mage::app()->getStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        Mage::getResourceModel('catalog/product_collection')->setStore($adminStore->getId());

        $lastPageNumber = PHP_INT_MAX;
        $page = 0;
        $ids = $this->productList->getProductList();
        while ($page <= $lastPageNumber) {
            $collection = $this->getProductCollection($ids, $page, $adminStore->getId());
            $lastPageNumber = $collection->getLastPageNumber();

            foreach ($collection as $product) {
                try {
                    /** @var $product Mage_Catalog_Model_Product */
                    $gcProduct = $this->createCompanyProduct($product);

                    // company product is created before any channel product, therefore gc_product_ids is empty
                    // and we don't need to merge anything
                    $product->setGcProductIds(json_encode(array('company' => $gcProduct->getId())));
                    $this->productList->removeProductId($product->getId());
                    die();
                } catch (Mage_Core_Exception $e) {
                    // TODO handle exception
                    throw $e;
                }
            }
            $page++;

        }
        $this->productList->save();
    }

    private function createCompanyProduct(Mage_Catalog_Model_Product $product)
    {
        return $this->getApi()->createCompanyProduct($product);
    }

    /**
     * @param array $views
     */
    private function createChannelProducts(array $views)
    {

    }

    /**
     * @param array $ids
     * @param int   $page
     * @param int   $storeId
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    private function getProductCollection($ids, $page, $storeId)
    {
        /* @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->addIdFilter($ids)
            ->addAttributeToSelect('*')
            ->addAttributeToSort('type_id', Mage_Catalog_Model_Resource_Product_Collection::SORT_ORDER_DESC)
            ->setFlag('require_stock_items')
            ->setPageSize(self::PAGE_SIZE)
            ->setCurPage($page);

        return Mage::helper('goodscloud_sync/product')->addMediaGalleryAttributeToCollection($collection, $storeId);
    }
}
