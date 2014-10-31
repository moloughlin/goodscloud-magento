<?php

class GoodsCloud_Sync_Test_Model_FirstWrite_Categories extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @loadFixture storeForCategory.yaml
     * @loadFixture categories.yaml
     */
    public function testCreateCategories()
    {
        $apiMock = $this->getModelMock('goodscloud_sync/api', array('createCategory'), false, array(), '', false);

        $apiMock->expects($this->exactly(5)) // (4 categories + store view root category) * 1 storeview
        ->method('createCategory')
            ->will(
                $this->returnCallback(
                    function (Mage_Catalog_Model_Category $category) {
                        $categoryData = new Varien_Object();
                        $categoryData->setData(
                            array(
                                'channel_id' => 126,
                                'description' => '',
                                'external_identifier' => $category->getId(),
                                'id' => mt_rand(),
                                'label' => $category->getName(),
                            )
                        );

                        return $categoryData;
                    }
                )
            );

        $stores = Mage::app()->getStores();

        $firstWriteCategories = Mage::getModel('goodscloud_sync/firstWrite_categories');
        /** @var GoodsCloud_Sync_Model_Api $apiMock */
        $firstWriteCategories->setApi($apiMock);
        $firstWriteCategories->createCategories($stores);

        // check that the gc category ids are added to the categories
        $reflection = new ReflectionProperty(get_class($firstWriteCategories), 'categories');
        $reflection->setAccessible(true);
        $categories = $reflection->getValue($firstWriteCategories);

        foreach ($categories as $category) {
            $this->assertJson($category->getGcCategoryIds());
            $this->assertNotEmpty($category->getGcCategoryIds());
        }
    }
}
