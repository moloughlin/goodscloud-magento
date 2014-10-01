<?php

class GoodsCloud_Sync_Model_FirstWrite_Products extends GoodsCloud_Sync_Model_FirstWrite_Base
{
    /**
     * number of products which are exported in one loop
     */
    const PAGE_SIZE = 100;

    /**
     * @var GoodsCloud_Sync_Model_FirstWrite_ProductList[]
     */
    private $productLists = array();

    /**
     * @var GoodsCloud_Sync_Helper_Api
     */
    private $apiHelper;

    function __construct()
    {
        $this->apiHelper = Mage::helper('goodscloud_sync/api');
    }

    /**
     * create a list of all products and export them to goodscloud
     *
     * @param Mage_Core_Model_Store[] $views
     *
     * @return bool
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    public function createProducts($views)
    {
        foreach ($views as $view) {
            $this->productLists[$view->getId()] = Mage::getModel('goodscloud_sync/firstWrite_productList')
                ->setFlagCode('goodscloud_channel_product_list_' . $view->getId())
                ->loadSelf();
        }
        if (!$this->isFinished()) {
            $this->prepareProductLists();
            $this->createCompanyAndChannelProducts($views);
        }

        return true;
    }

    /**
     * check whether all products were exported
     *
     * @return bool
     */
    private function isFinished()
    {
        $oneUnfinished = true;
        foreach ($this->productLists as $lists) {
            if (!$lists->isFinished()) {
                $oneUnfinished = false;
            }
        }
        return $oneUnfinished;
    }

    /**
     * @param Mage_Core_Model_Store $view
     *
     * @return \GoodsCloud_Sync_Model_FirstWrite_ProductList
     */
    private function getProductList(Mage_Core_Model_Store $view)
    {
        return $this->productLists[$view->getId()];
    }

    /**
     * save a list of all products to be exported to know which already are exported
     */
    private function prepareProductLists()
    {
        $apiHelper = Mage::helper('goodscloud_sync/api');

        $collection = Mage::getResourceModel('catalog/product_collection');
        // only export physical items
        $collection->addFieldToFilter(
            'type_id', array(
                'in' => array(
                    Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
                    Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL,
                    //                    Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
                    //                    Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
                    //                    Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
                )
            )
        )->addAttributeToFilter(
            array(
                array('attribute' => $apiHelper->getIdentifierAttribute(), array('notnull' => true)),
                array('attribute' => $apiHelper->getIdentifierAttribute(), array('neq' => ''))
            )
        );
        $allIds = $collection->getAllIds();

        foreach ($this->productLists as $list) {
            /* @var $list GoodsCloud_Sync_Model_FirstWrite_ProductList */
            if (!$list->isFilled()) {
                $this->log('Added all IDs to the queue: ' . implode(', ', $allIds));
                $list->setProductList($allIds);
            }
        }
    }

    private function createCompanyProduct(Mage_Catalog_Model_Product $product)
    {
        return $this->getApi()->createCompanyProduct($product);
    }

    private function createChannelProduct(Mage_Catalog_Model_Product $product, Mage_Core_Model_Store $store)
    {
        return $this->getApi()->createChannelProduct($product, $store);
    }

    /**
     * @param array $views
     *
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    private function createCompanyAndChannelProducts(array $views)
    {
        /** @see http://magento.stackexchange.com/a/25908/217 */
        // It is intended to create two collections! Don't change this, because of a core bug!

        foreach ($views as $view) {
            Mage::log('View: ' . $view->getCode() . '(' . $view->getId() . ')');
            Mage::getResourceModel('catalog/product_collection')->setStore($view->getId());

            $lastPageNumber = PHP_INT_MAX;
            $page = 0;
            while ($page <= $lastPageNumber) {
                $ids = $this->getProductList($view)->getProductList();
                if (empty($ids)) {
                    break;
                }
                Mage::log("Page: $page von $lastPageNumber");
                $collection = $this->getProductCollection($ids, $page, $view->getId());
                $lastPageNumber = $collection->getLastPageNumber();

                foreach ($collection as $product) {
                    try {
                        if (!$this->apiHelper->getGcProductId($product, $view->getId())) {
                            $this->createGcProductAndUpdateProduct($view, $product);
                        }
                        $this->getProductList($view)->removeProductId($product->getId());
                    } catch (Mage_Core_Exception $e) {
                        $collection->save();
                        // TODO handle exception
                        throw $e;
                    }
                }
                $collection->save();
                $page++;
            }
            $this->getProductList($view)->save();
        }
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
            ->setStoreId($storeId);

        $collection->addIdFilter($ids)
            ->addAttributeToSelect('*')
            ->addAttributeToSort('entity_id')
            ->setFlag('require_stock_items')
            ->setPageSize(self::PAGE_SIZE)
            ->setCurPage($page);

        return Mage::helper('goodscloud_sync/product')->addMediaGalleryAttributeToCollection($collection, $storeId);
    }

    /**
     * @param Mage_Core_Model_Store      $view
     * @param Mage_Catalog_Model_Product $product
     */
    private function createGcProductAndUpdateProduct(Mage_Core_Model_Store $view, Mage_Catalog_Model_Product $product)
    {
        if ($view->getCode() == Mage_Core_Model_Store::ADMIN_CODE) {
            /** @var $product Mage_Catalog_Model_Product */
            $gcProduct = $this->createCompanyProduct($product);
        } else {
            /** @var $product Mage_Catalog_Model_Product */
            $gcProduct = $this->createChannelProduct($product, $view);
        }

        $this->apiHelper->addGcProductId($product, $gcProduct->getId(), $view->getId());
    }
}
