<?php

abstract class GoodsCloud_Sync_Model_FirstWrite_AbstractProduct
    extends GoodsCloud_Sync_Model_FirstWrite_Base
{
    /**
     * number of products which are exported in one loop
     */
    const PAGE_SIZE = 100;

    /**
     * @var GoodsCloud_Sync_Model_FirstWrite_ProductList[]
     */
    protected $productLists = array();

    /**
     * @var GoodsCloud_Sync_Helper_Api
     */
    private $apiHelper;

    /**
     *
     */
    function __construct()
    {
        $this->apiHelper = Mage::helper('goodscloud_sync/api');
    }

    /**
     * create a list of all products and export them to goodscloud
     *
     * @param Mage_Core_Model_Store[] $views
     *
     * @return GoodsCloud_Sync_Model_FirstWrite_AbstractProduct
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    public function createProducts($views)
    {
        foreach ($views as $view) {
            $this->productLists[$view->getId()]
                = Mage::getModel('goodscloud_sync/firstWrite_productList')
                ->setFlagCode(
                    'goodscloud_channel_product_list_' . $view->getId()
                )
                ->loadSelf();
        }
        if (!$this->isFinished()) {
            $this->prepareProductLists();
            $this->createCompanyAndChannelProducts($views);
        }

        return $this;
    }

    /**
     * check whether all products were exported
     *
     * @return bool
     */
    public function isFinished()
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
    protected function getProductList(Mage_Core_Model_Store $view)
    {
        if (!isset($this->productLists[$view->getId()])) {
            throw new LogicException('Product list not created yet.');
        }
        return $this->productLists[$view->getId()];
    }

    /**
     * save a list of all products to be exported to know which already
     * are exported
     */
    protected function prepareProductLists()
    {
        $apiHelper = Mage::helper('goodscloud_sync/api');

        $collection = Mage::getResourceModel('catalog/product_collection');
        // only export physical items
        $collection->addFieldToFilter(
            'type_id', array(
                'in' => $this->getExportedTypes(),
            )
        )->addAttributeToFilter(
            array(
                array(
                    'attribute' => $apiHelper->getIdentifierAttribute(),
                    array('notnull' => true)
                ),
                array(
                    'attribute' => $apiHelper->getIdentifierAttribute(),
                    array('neq' => '')
                )
            )
        );
        $allIds = $collection->getAllIds();

        foreach ($this->productLists as $list) {
            /* @var $list GoodsCloud_Sync_Model_FirstWrite_ProductList */
            if (!$list->isFilled()) {
                $implodedIds = implode(', ', $allIds);
                $this->log('Added all IDs to the queue: ' . $implodedIds);
                $list->setProductList($allIds);
            }
        }
    }

    /**
     * @param Mage_Core_Model_Store[] $views
     *
     * @return void
     */
    abstract protected function createCompanyAndChannelProducts(
        array $views
    );

    /**
     * @return array
     */
    abstract protected function getExportedTypes();

    /**
     * @param array $ids
     * @param int   $page
     * @param int   $storeId
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function getProductCollection($ids, $page, $storeId)
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

        // only add images for admin store, because the collection is loaded,
        // when the images are added and to have later up to date products, they
        // have to be loaded AFTER all others are finished
        if ($storeId == Mage_Core_Model_App::ADMIN_STORE_ID) {
            return Mage::helper('goodscloud_sync/product')
                ->addMediaGalleryAttributeToCollection($collection, $storeId);
        }
        return $collection;
    }

    /**
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     * @param Mage_Core_Model_Store                          $view
     *
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    protected function exportProductCollectionPage($collection, $view)
    {
        foreach ($collection as $product) {
            try {
                if (!$this->apiHelper->getGcProductId(
                    $product, $view->getId()
                )
                ) {
                    $this->createGcProductAndUpdateProduct($view, $product);
                }
                $this->getProductList($view)->removeProductId(
                    $product->getId()
                );
            } catch (Mage_Core_Exception $e) {
                $collection->save();
                throw $e;
            }
        }
    }

    /**
     * @param Mage_Core_Model_Store      $view
     * @param Mage_Catalog_Model_Product $product
     *
     * @return mixed
     */
    abstract protected function createGcProductAndUpdateProduct(
        Mage_Core_Model_Store $view,
        Mage_Catalog_Model_Product $product
    );

    /**
     * @return bool
     */
    protected function allCompanyProductsAreCreated()
    {
        $adminStoreId = Mage_Core_Model_App::ADMIN_STORE_ID;
        return !((bool)count($this->productLists[$adminStoreId]));
    }
}
