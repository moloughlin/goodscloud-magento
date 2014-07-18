<?php

class GoodsCloud_Sync_Model_FirstWrite_Categories extends GoodsCloud_Sync_Model_FirstWrite_Base
{
    /**
     * @var Mage_Catalog_Model_Resource_Category_Collection
     */
    private $categories;

    /**
     * all category ids for the current store view with the corresponding gc category ids
     *
     * @var array
     */
    private $categoryIdCache = array();

    /**
     * create categories in the different channels
     *
     * @param Mage_Core_Model_Store[] $storeViews
     */
    public function createCategories(array $storeViews)
    {
        foreach ($storeViews as $view) {
            $this->categoryIdCache = array();
            if (!$view->getGcChannelId()) {
                Mage::throwException(sprintf('Store %s has no gc channel id set!', $view->getName()));
            }

            // Builds an expression like ^1/2(/.*)$
            $categoryIdPrefix = '^' . Mage_Catalog_Model_Category::TREE_ROOT_ID . '/' . $view->getRootCategoryId() . '(/.*)?$';
            $this->categories = Mage::getResourceModel('catalog/category_collection')
                ->addPathFilter($categoryIdPrefix)
                ->addAttributeToSelect(
                    array(
                        'gc_channel_id',
                        'name',
                        'is_active',
                        'is_anchor',
                    )
                )
                ->setOrder('level');

            $gcParentId = null;
            foreach ($this->categories as $category) {
                /* @var $category Mage_Catalog_Model_Category */
                $categoryIds = json_decode($category->getGcCategoryIds(), true);
                if (!isset($categoryIds[$view->getGcChannelId()])) {

                    // if the parent is the root of the store, set it NULL so it is the root in goodscloud
                    if ($category->getId() != $view->getRootCategoryId()) {
                        $gcParentId = $this->categoryIdCache[$category->getParentId()];
                    }

                    $categoryData = $this->createCategory($category, $view, $gcParentId);

                    if (!$categoryData) {
                        Mage::throwException('Error while creating category');
                    }

                    $categoryIds[$view->getGcChannelId()] = $categoryData->id;
                    $category->setGcCategoryIds(json_encode($categoryIds));
                    $category->save();
                }
                $this->categoryIdCache[$category->getId()] = $categoryIds[$view->getGcChannelId()];
            }
        }
    }

    /**
     * create category in goodscloud
     *
     * @param Mage_Catalog_Model_Category $category   category to create
     * @param Mage_Core_Model_Store       $store      channel (storeview) to create the category in
     * @param int                         $gcParentId parent id of the gc category
     *
     * @return string|void
     */
    private function createCategory(Mage_Catalog_Model_Category $category, Mage_Core_Model_Store $store, $gcParentId)
    {
        return $this->getApi()->createCategory($category, $store, $gcParentId);
    }

    /**
     * get goodscloud parent id for category
     *
     * @param Mage_Catalog_Model_Category $category category to get parent for
     * @param Mage_Core_Model_Store       $view     "channel" to get parent for
     *
     * @return int id of parent category
     */
    private function getGcParentId(Mage_Catalog_Model_Category $category, $view)
    {
        $parentCategory = $this->categories->getItemById($category->getParentId());
        if (!$parentCategory) {
            Mage::throwException(sprintf('Parent category for %s not found.', $category->getName()));
        }
        $parentGcIds = json_decode($parentCategory->getGcCategoryIds());

        $gcParentId = isset($parentGcIds[$view->getGcChannelId()]) ? $parentGcIds[$view->getGcChannelId()] : false;

        if (!$gcParentId) {
            Mage::throwException(
                sprintf('Parent category of %s was not written to goodscloud yet!', $category->getName())
            );
        }

        return $gcParentId;
    }
}
