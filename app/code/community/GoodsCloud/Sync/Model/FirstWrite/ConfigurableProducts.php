<?php

class GoodsCloud_Sync_Model_FirstWrite_ConfigurableProducts
    extends GoodsCloud_Sync_Model_FirstWrite_AbstractProduct
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
     * @return bool
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    public function createProducts($views)
    {
        foreach ($views as $view) {
            $this->productLists[$view->getId()]
                = Mage::getModel('goodscloud_sync/firstWrite_productList')
                ->setFlagCode(
                    'goodscloud_channel_product_view_list_' . $view->getId()
                )
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
     * @param Mage_Catalog_Model_Product $product
     *
     * @return GoodsCloud_Sync_Model_Api_Company_Product
     */
    private function createCompanyProductView(
        Mage_Catalog_Model_Product $product
    ) {
        return $this->getApi()->createCompanyProductView($product);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Core_Model_Store      $store
     *
     * @return GoodsCloud_Sync_Model_Api_Channel_Product
     */
    private function createChannelProductView(
        Mage_Catalog_Model_Product $product,
        Mage_Core_Model_Store $store
    ) {
        return $this->getApi()->createChannelProductView($product, $store);
    }

    /**
     * @param Mage_Core_Model_Store[] $views
     *
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    protected function createCompanyAndChannelProducts(array $views)
    {


        foreach ($views as $view) {
            // make sure to export channel products after _ALL_ company products are created
            if ($view->getId() != Mage_Core_Model_App::ADMIN_STORE_ID
                && !$this->allCompanyProductsAreCreated()
            ) {
                throw new RuntimeException('Not all company products created yet');
            }

            Mage::log('View: ' . $view->getCode() . '(' . $view->getId() . ')');

            /** @see http://magento.stackexchange.com/a/25908/217 */
            // It is intended to create two collections! Don't change this,
            // because of a core bug!
            Mage::getResourceModel('catalog/product_collection')
                ->setStore($view->getId());

            $numberOfPages = $this->getNumberOfPages(
                $this->getProductList($view)->getProductList()
            );

            for ($page = 1; $page <= $numberOfPages; $page++) {

                $ids = $this->getProductList($view)->getProductList();
                if (empty($ids)) {
                    break;
                }
                Mage::log("Page: $page von $numberOfPages");
                $collection = $this->getProductCollection(
                    $ids,
                    $page,
                    $view->getId()
                );

                $this->exportProductCollectionPage($collection, $view);
                $collection->save();
            }
        }
        if (isset($view)) {
            $this->getProductList($view)->save();
        }
    }

    private function getNumberOfPages(
        GoodsCloud_Sync_Model_FirstWrite_ProductList $collection
    ) {
        $entries = count($collection);
        return ceil($entries / self::PAGE_SIZE);
    }

    /**
     * @param Mage_Core_Model_Store      $view
     * @param Mage_Catalog_Model_Product $product
     *
     * @return void
     */
    protected function createGcProductAndUpdateProduct(
        Mage_Core_Model_Store $view,
        Mage_Catalog_Model_Product $product
    ) {
        if ($view->getCode() == Mage_Core_Model_Store::ADMIN_CODE) {
            /** @var $product Mage_Catalog_Model_Product */
            $gcProduct = $this->createCompanyProductView($product);
        } else {
            /** @var $product Mage_Catalog_Model_Product */
            $gcProduct = $this->createChannelProductView($product, $view);
        }

        $this->apiHelper->addGcProductId(
            $product,
            $gcProduct->getId(),
            $view->getId()
        );
    }

    /**
     * @return array
     */
    function getExportedTypes()
    {
        return array(
            Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
        );
    }
}
