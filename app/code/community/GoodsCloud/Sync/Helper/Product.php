<?php

class GoodsCloud_Sync_Helper_Product extends Mage_Core_Helper_Abstract
{

    /**
     * add media gallery images to collection
     *
     * @param Mage_Catalog_Model_Resource_Product_Collection $productCollection
     * @param  int                                           $storeId
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     *
     * @todo find a better way to add the product images!
     * @see  http://www.magentocommerce.com/boards/viewthread/17414/#t141830
     */
    public function addMediaGalleryAttributeToCollection(
        Mage_Catalog_Model_Resource_Product_Collection $productCollection, $storeId
    ) {
        $mediaGalleryAttributeId = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'media_gallery')
            ->getAttributeId();
        $readConnection = Mage::getSingleton('core/resource')->getConnection('catalog_read');

        $mediaGalleryData = $readConnection->fetchAll(
            '
        SELECT
            main.entity_id, `main`.`value_id`, `main`.`value` AS `file`,
            `value`.`label`, `value`.`position`, `value`.`disabled`, `default_value`.`label` AS `label_default`,
            `default_value`.`position` AS `position_default`,
            `default_value`.`disabled` AS `disabled_default`
        FROM `catalog_product_entity_media_gallery` AS `main`
            LEFT JOIN `catalog_product_entity_media_gallery_value` AS `value`
                ON main.value_id=value.value_id AND value.store_id=' . $storeId . '
            LEFT JOIN `catalog_product_entity_media_gallery_value` AS `default_value`
                ON main.value_id=default_value.value_id AND default_value.store_id=0
        WHERE (
            main.attribute_id = ' . $readConnection->quote($mediaGalleryAttributeId) . ')
            AND (main.entity_id IN (' . $readConnection->quote($productCollection->getAllIds()) . '))
        ORDER BY IF(value.position IS NULL, default_value.position, value.position) ASC
    '
        );

        $mediaGalleryByProductId = array();
        foreach ($mediaGalleryData as $galleryImage) {
            $k = $galleryImage['entity_id'];
            unset($galleryImage['entity_id']);
            if (!isset($mediaGalleryByProductId[$k])) {
                $mediaGalleryByProductId[$k] = array();
            }
            $mediaGalleryByProductId[$k][] = $galleryImage;
        }
        unset($mediaGalleryData);

        foreach ($productCollection as $product) {
            $productId = $product->getData('entity_id');
            if (isset($mediaGalleryByProductId[$productId])) {
                $product->setData('media_gallery', array('images' => $mediaGalleryByProductId[$productId]));
            }
        }
        unset($mediaGalleryByProductId);

        return $productCollection;
    }
}
