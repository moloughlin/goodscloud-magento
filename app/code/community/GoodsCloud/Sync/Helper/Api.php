<?php

class GoodsCloud_Sync_Helper_Api extends Mage_Core_Helper_Abstract
{
    const XML_CONFIG_IDENTIFIER_TYPE = 'goodscloud_sync/shop/identifier_type';
    const XML_CONFIG_IDENTIFIER_ATTRIBUTE = 'goodscloud_sync/shop/identifier_attribute';
    const XML_CONFIG_BASE_URL = 'goodscloud_sync/advanced/base_url';
    const XML_CONFIG_EMAIL = 'goodscloud_sync/basic/username';
    const XML_CONFIG_PASSWORD = 'goodscloud_sync/basic/password';
    const XML_CONFIG_IGNORED_ATTRIBUTES = 'goodscloud_sync/api/ignored_attributes';
    const XML_CONFIG_BOOLEAN_SOURCE_MODELS = 'goodscloud_sync/api/boolean_source_models';
    const XML_CONFIG_ENUM_TYPES = 'goodscloud_sync/api/enum_types';

    const XML_CONFIG_VAT_RATES = 'goodscloud_sync/api/vat_rate';

    const XML_CONFIG_COMPANY_ID = 'goodscloud_sync/api/company_id';
    const XML_CONFIG_DEFAULT_PRICE_LIST_ID = 'goodscloud_sync/api/default_price_list_id';
    const XML_CONFIG_DEFAULT_VAT_RATE_ID = 'goodscloud_sync/api/default_vat_rate_id';

    /**
     * @var Mage_Catalog_Model_Entity_Attribute
     */
    private $gcProductIdAttribute;

    /**
     * array to map attributesets for the different stores on the property sets
     *
     * format:
     * $array[attributesetId][$storeViewId] = $propertySetId
     *
     * @var array
     */
    private $attr2PropSet;

    /**
     * array of format:
     *
     * array (attributeSetId => array(attributes))
     *
     * @var array
     */
    private $attributesInSet;

    /**
     *
     */
    private function initAttributeSetMapping()
    {
        $productEntityType = Mage::getModel('eav/entity_type')
            ->loadByCode(Mage_Catalog_Model_Product::ENTITY);

        $attributeSetCollection
            = Mage::getResourceModel('eav/entity_attribute_set_collection');
        $attributeSetCollection->setEntityTypeFilter($productEntityType->getId());
        foreach ($attributeSetCollection as $attrSet) {
            $propertySetIds = json_decode($attrSet->getGcPropertySetIds(),
                true);
            foreach (Mage::app()->getStores() as $store) {
                if (isset($propertySetIds[$store->getGcChannelId()])) {
                    $this->attr2PropSet[$attrSet->getId()][$store->getId()]
                        = $propertySetIds[$store->getGcChannelId()];
                }
            }
        }
    }

    /**
     * create the array to get all attributes in one set
     */
    private function initAttributesToAttributeSetMapping()
    {
        $productEntityType = Mage::getModel('eav/entity_type')
            ->loadByCode(Mage_Catalog_Model_Product::ENTITY);

        $attributes = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setEntityTypeFilter($productEntityType->getId())
            ->addSetInfo()
            ->getData();


        foreach ($attributes as $attribute) {
            /** @var $attribute Mage_Eav_Model_Entity_Attribute */
            foreach (
                array_keys($attribute['attribute_set_info']) as $attributeSetId
            ) {
                $this->attributesInSet[$attributeSetId][]
                    = $attribute['attribute_code'];
            }
        }
    }

    /**
     * get the attribute which are not ignores
     *
     * @param Mage_Catalog_Model_Product $product
     */
    private function getAttributes(Mage_Catalog_Model_Product $product)
    {
        if (empty($this->attributesInSet)) {
            $this->initAttributesToAttributeSetMapping();
        }

        return $this->attributesInSet[$product->getAttributeSetId()];
    }

    /**
     * get all attributes which should be exported as json
     *
     * make sure the product is in the right context and loaded the correct scope!
     *
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Core_Model_Store      $store
     *
     * @return string
     */
    public function getPropertiesWithValues(
        Mage_Catalog_Model_Product $product,
        Mage_Core_Model_Store $store = null
    ) {
        $properties = array();
        foreach ($this->getAttributes($product) as $attributeCode) {
            $ignoredAttributes = $this->getIgnoredAttributes();
            if (!in_array($attributeCode, $ignoredAttributes)) {
                $attrValue = $product->getAttributeText($attributeCode);
                $properties[$attributeCode] = $attrValue ? $attrValue
                    : $product->getDataUsingMethod($attributeCode);
            }
        }
        return $properties;
    }

    /**
     * get the baseuri for api requests
     *
     * @return string
     */
    public function getUri()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_BASE_URL);
    }

    /**
     * email address to log into goodscloud api
     *
     * @return string
     */
    public function getEmail()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_EMAIL);
    }

    /**
     * password to log into goodscloud api
     *
     * @return string
     */
    public function getPassword()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_PASSWORD);
    }

    /**
     * @return array
     */
    public function getIgnoredAttributes()
    {
        return array_keys(Mage::getStoreConfig(self::XML_CONFIG_IGNORED_ATTRIBUTES));
    }

    /**
     * @return array
     */
    public function getBooleanSourceModels()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_BOOLEAN_SOURCE_MODELS);
    }

    /**
     * @return array
     */
    public function getEnumTypes()
    {
        return array_keys(Mage::getStoreConfig(self::XML_CONFIG_ENUM_TYPES));
    }

    /**
     * determine type in goodscloud
     *
     * goodscloud knows types:
     * - free
     * - enum (select, multiselect)
     * - range (doesn't exist in magento)
     * - bool (depends on source_model = boolean, yes_no)
     * - datetime
     *
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     *
     * @return string
     */
    public function getPropertySchemaTypeForAttribute(
        Mage_Eav_Model_Entity_Attribute $attribute
    ) {
        if (in_array($attribute->getFrontendInput(),
            array('select', 'multiselect'))) {
            // it is enum or bool
            if (in_array($attribute->getSourceModel(),
                array('eav/entity_attribute_source_boolean'))) {
                return 'bool';
            } else {
                return 'enum';
            }
        }

        if ($attribute->getBackendType() == 'datetime') {
            return 'datetime';
        }
        return 'free';
    }

    /**
     * @param Mage_Eav_Model_Entity_Attribute $attribute attribute to get options for
     *
     * @return array options for attribute
     * @throws Mage_Core_Exception
     */
    public function getPropertySchemaValuesForAttribute(
        Mage_Eav_Model_Entity_Attribute $attribute
    ) {
        try {
            $values = array();
            foreach ($attribute->getSource()->getAllOptions() as $option) {
                if ($option['value']) {
                    $values[] = $option['value'];
                }
            }
            return $values;
        } catch (Mage_Core_Exception $e) {
            $sourceModelNotFound = 'Source model "" not found for attribute ';
            $length = strlen($sourceModelNotFound);
            if (substr($e->getMessage(), 0, $length) == $sourceModelNotFound) {
                return array();
            }
            throw $e;
        }
    }

    /**
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     *
     * @return bool
     */
    public function isAttributeMultiValue(
        Mage_Eav_Model_Entity_Attribute $attribute
    ) {
        return $attribute->getFrontendInput() == 'multiselect';
    }

    /**
     * @return string
     */
    public function getIdentifierType()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_IDENTIFIER_TYPE);
    }

    /**
     * @return string
     */
    public function getIdentifierAttribute()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_IDENTIFIER_ATTRIBUTE);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Core_Model_Store      $store
     *
     * @return array
     */
    public function getDescriptionData(
        Mage_Catalog_Model_Product $product,
        Mage_Core_Model_Store $store = null
    ) {
        $store = Mage::app()->getStore($store);
        /** @var $apiHelper GoodsCloud_Sync_Helper_Api */
        $apiHelper = Mage::helper('goodscloud_sync/api');
        $descriptions
            = array(
            array(
                //    id	column	Integer	not NULL Primary key.
                //    chosen_channel_product_views	relationship	List of ChannelProductView entries.
                //    chosen_channel_products	relationship	List of ChannelProduct entries.
                //    company_product_views	relationship	List of CompanyProductView entries.
                //    company_products	relationship	List of CompanyProduct entries.
                //    label	column	String 256 characters or less.
                'label'             => $store->getName(),
                //    language_code	column	LowercaseEnum	not NULL The language for this description. Must be ISO-639 codes
                'language_code'     => $apiHelper->getLanguage($store),
                //    long_description	column	Text Any length allowed.
                'long_description'  => $product->getDescription(),
                //    rights	column	String 10 characters or less. the rights to the description, might change to an enum
                //    short_description	column	Text Any length allowed.
                'short_description' => $product->getShortDescription(),
                //    updated	column	DateTime	not NULL ISO format datetime with timezone offset: 1997-07-16T19:20:30.45+01:00. The time when this row was last updated. Read-only.
                //    version	column	Integer	not NULL	1 Current version number of this entry, incremented each time it is changed. Read-only.
                //    company_id	column	Integer	not NULL ForeignKey('company.id') ON DELETE CASCADE
                'company_id'        => $apiHelper->getCompanyId(),
                //    company	relationship	Single Company entry.
                //    created	hybrid_property The time when this row was created. Determin  ed by looking in the history for this table. Read-only.
            )
        );

        return $descriptions;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return array
     */
    public function createImages(Mage_Catalog_Model_Product $product)
    {
        /** @var $apiHelper GoodsCloud_Sync_Helper_Api */
        $apiHelper = Mage::helper('goodscloud_sync/api');

        $images = array();
        $mediaGalleryImages = $product->getMediaGallery();
        if (is_array($mediaGalleryImages)
            && is_array($mediaGalleryImages['images'])
        ) {
            foreach ($mediaGalleryImages['images'] as $image) {
                $imageInfo = getimagesize($product->getMediaConfig()
                    ->getMediaPath($image['file']));

                $images[] = array(
                    //            id	column	Integer	not NULL Primary key.
                    //            company_product_views	relationship	List of CompanyProductView entries.
                    //            company_products	relationship	List of CompanyProduct entries.
                    //            alt_text	column	Text Any length allowed. alt attribute text
                    'alt_text'            => $image['label'],
                    //            external_identifier	column	String 512 characters or less. If this is a valid http:// or https:// URL, the image is processed and uploaded to Amazon S3 on PUT and PATCH requests. If you have multiple image sizes available, please submit the largest version.
                    'external_identifier' => $product->getMediaConfig()
                        ->getMediaUrl($image['file']),
                    //            height	column	Integer
                    'height'              => $imageInfo[1],
                    //            mimetype	column	String 32 characters or less.
                    'mimetype'            => $imageInfo['mime'],
                    //            rights	column	String 10 characters or less. The rights to the image, might change to an enum
                    //            updated	column	DateTime	not NULL ISO format datetime with timezone offset: 1997-07-16T19:20:30.45+01:00. The time when this row was last updated. Read-only.
                    //            url_fragment	column	String 512 characters or less.
                    //            version	column	Integer	not NULL	1  Current version number of this entry, incremented each time it is changed. Read-only.
                    //            width	column	Integer
                    'width'               => $imageInfo[0],
                    //            company_id	column	Integer	not NULL ForeignKey('company.id') ON DELETE CASCADE
                    'company_id'          => $apiHelper->getCompanyId(),
                    //            company	relationship	Single Company entry.
                    //            created	hybrid_property The time when this row was created. Determined by looking in the history for this table. Read-only.
                    //            channel_product_views	relationship	List of ChannelProductView entries.
                    //            channel_products	relationship	List of ChannelProduct entries.
                );
            }
        }

        return $images;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return array
     */
    public function createPrices(
        Mage_Catalog_Model_Product $product,
        GoodsCloud_Sync_Model_Api $api
    ) {
        $prices = array();

        $prices[] = array(
            //id	column	Integer	not NULL Primary key.
            //minimum_quantity	column	Integer	not NULL	1
            'minimum_quantity' => 1,
            //net	column	Numeric	not NULL 00000000.00 Net monetary price
            'net'              => $product->getPriceModel()
                ->getFinalPrice(1, $product),
            //updated	column	DateTime	not NULL ISO format datetime with timezone offset: 1997-07-16T19:20:30.45+01:00.The time when this row was last updated. Read-only.
            //vat_amount	column	Numeric 00000000.00 VAT amount for this net price
            //version	column	Integer	not NULL	1 Current version number of this entry, incremented each time it is changed. Read-only.
            //company_product_id	column	Integer	not NULL ForeignKey('company_product.id') ON DELETE CASCADE
            //company_product	relationship	Single CompanyProduct entry.
            //price_list_id	column	Integer	not NULL ForeignKey('price_list.id') ON DELETE CASCADE
            'price_list_id'    => $this->getDefaultPriceListId(),
            //price_list	relationship	Single PriceList entry.
            'vat_rate_id'      => $this->createIfNeededAndGetVatRateId(
                $product,
                $api
            ),
            //vat_rate_id	column	Integer	not NULL ForeignKey('vat_rate.id') ON DELETE RESTRICT The VAT rate originally used for calculating VAT amount.
            //vat_rate	relationship	Single VatRate entry.
            //created	hybrid_property The time when this row was created. Determined by looking in the history for this table. Read-only.
            //gross	hybrid_property The gross price for this net price and VAT rate. Read-only.
        );

        return $prices;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return int
     */
    private function createIfNeededAndGetVatRateId(
        Mage_Catalog_Model_Product $product,
        GoodsCloud_Sync_Model_Api $api,
        Mage_Core_Model_Store $store = null
    ) {
        $calc = Mage::getSingleton('tax/calculation');
        $rates = $calc->getRatesForAllProductTaxClasses(
            $calc->getDefaultRateRequest($store)
        );

        $taxClass = Mage::getModel('tax/class');

        // special case, tax class with id doesn't exist

        if ($product->getTaxClassId() == 0) {
            $rate = 0;
            $taxClass->setClassName('None');
        } else {
            $rate = $rates[$product->getTaxClassId()];

            $taxClass->load($product->getTaxClassId());
        }


        return $this->createVatRateIfNeeded($api, $taxClass, $rate);
    }

    /**
     * @param GoodsCloud_Sync_Model_Api $api
     * @param Mage_Tax_Model_Class      $taxClass
     * @param float                     $rate
     *
     * @return int
     */
    private function createVatRateIfNeeded(
        GoodsCloud_Sync_Model_Api $api,
        Mage_Tax_Model_Class $taxClass,
        $rate
    ) {
        $label = $taxClass->getClassName() . ' ' . $rate;

        if (!$this->getVateRateId($label)) {
            $rate = $api->createVatRate(
                array(
                    'label' => $label,
                    'rate'  => $rate,
                )
            );
            $this->setVatRateId($label, $rate->getId());
        }
        return $this->getVateRateId($label);
    }

    /**
     * @param Mage_Sales_Model_Order_Item $item
     *
     * @return float
     */
    public function getRateIdForItem(
        Mage_Sales_Model_Order_Item $item,
        GoodsCloud_Sync_Model_Api $api
    ) {

        $taxClass = Mage::getModel('tax/class');

        // special case, tax class with id doesn't exist

        if ($item->getProduct()->getTaxClassId() == 0) {
            $taxClass->setClassName('None');
        } else {
            $taxClass->load($item->getProduct()->getTaxClassId());
        }

        return $this->createVatRateIfNeeded(
            $api,
            $taxClass,
            $item->getTaxPercent()
        );
    }

    /**
     * @param string $label
     *
     * @return int
     */
    private function getVateRateId($label)
    {
        $cleanLabel = preg_replace('#[^a-zA-Z0-9]#', '_', $label);
        return Mage::getStoreConfig(self::XML_CONFIG_VAT_RATES . $cleanLabel);
    }

    private function setVatRateId($label, $id)
    {
        $cleanLabel = preg_replace('#[^a-zA-Z0-9]#', '_', $label);
        $config = Mage::app()->getConfig();
        $config->saveConfig(self::XML_CONFIG_VAT_RATES . $cleanLabel, $id);
        $config->reinit();
        $config->saveCache();
    }

    /**
     * get the company if from goodscloud
     *
     * @return int
     */
    public function getCompanyId()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_COMPANY_ID);
    }

    /**
     * save the company id from goodscloud in the config and refresh the cache
     *
     * reinit the config, so the value from DB is available in this request
     * and the cache is refreshed
     *
     * @param int $companyId
     */
    public function setCompanyId($companyId)
    {
        $config = Mage::app()->getConfig();
        $config->saveConfig(self::XML_CONFIG_COMPANY_ID, $companyId);
        $config->reinit();
        $config->saveCache();
    }

    /**
     * @return string
     */
    public function getDefaultPriceListId()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_DEFAULT_PRICE_LIST_ID);
    }

    /**
     * @param int $priceListId
     */
    public function setDefaultPriceListId($priceListId)
    {
        $config = Mage::app()->getConfig();
        $config->saveConfig(self::XML_CONFIG_DEFAULT_PRICE_LIST_ID,
            $priceListId);
        $config->reinit();
        $config->saveCache();
    }

    /**
     * @return int
     */
    public function getDefaultVatRate()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_DEFAULT_VAT_RATE_ID);
    }

    /**
     * @param int $vatRate
     */
    public function setDefaultVatRate($vatRate)
    {
        $config = Mage::app()->getConfig();
        $config->saveConfig(self::XML_CONFIG_DEFAULT_VAT_RATE_ID, $vatRate);
        $config->reinit();
        $config->saveCache();
    }

    /**
     * get channel id for store
     *
     * @param Mage_Core_Model_Store $store
     *
     * @return int
     */
    public function getChannelId(Mage_Core_Model_Store $store)
    {
        return $store->getGcChannelId();
    }

    /**
     * get the gc product id for a specific channel/store
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int                        $storeId
     *
     * @return int
     */
    public function getGcProductId(
        Mage_Catalog_Model_Product $product,
        $storeId
    ) {
        $json = json_decode($product->getGcProductIds(), true);
        if (isset($json[$storeId])) {
            return $json[$storeId];
        }
        return null;
    }

    /**
     * add a gc product id to a product for a specific store (channel)
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int                        $id
     * @param int                        $storeId
     */
    public function addGcProductId(
        Mage_Catalog_Model_Product $product,
        $id,
        $storeId
    ) {
        $json = json_decode($product->getGcProductIds(), true);
        $json[$storeId] = $id;
        $product->setGcProductIds(json_encode($json, JSON_FORCE_OBJECT));

    }

    /**
     * get the GC property set id from a product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Core_Model_Store      $store
     *
     * @return int
     */
    public function getPropertySetId(
        Mage_Catalog_Model_Product $product,
        Mage_Core_Model_Store $store
    ) {

        if (empty($this->attr2PropSet)) {
            $this->initAttributeSetMapping();
        }

        return $this->attr2PropSet[$product->getAttributeSetId()][$store->getId()];
    }

    /**
     * get the gc company id from a product
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return int
     */
    public function getCompanyProductId(Mage_Catalog_Model_Product $product)
    {
        return $this->getGcProductId($product,
            Mage_Core_Model_App::ADMIN_STORE_ID);
    }

    /**
     * is the product physical?
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return bool
     */
    public function isPhysical(Mage_Catalog_Model_Product $product)
    {
        // don't oversee the !
        return !in_array(
            $product->getTypeId(),
            array(
                Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL,
                Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE,
            )
        );
    }

    /**
     * @param Mage_Core_Model_Store $store
     *
     * @return string
     */
    public function getSourceOfTruth(Mage_Core_Model_Store $store = null)
    {
        return Mage::getStoreConfig('tax/calculation/price_includes_tax',
            $store) ? 'net' : 'gross';
    }

    /**
     * get language of store
     *
     * @param Mage_Core_Model_Store|int $store store object or store view id
     *
     * @return string
     */
    public function getLanguage($store = null)
    {
        $store = Mage::app()->getStore($store);
        return substr(Mage::getStoreConfig('general/locale/code', $store), 0,
            2);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return int
     */
    public function getPackagingUnit(Mage_Catalog_Model_Product $product)
    {
        /* @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
        $stockItem = $product->getStockItem();
        if ($stockItem) {
            if ($stockItem->getEnableQtyIncrements()
                && is_numeric($stockItem->getQtyIncrements())
            ) {
                return $stockItem->getQtyIncrements();
            }
        }
        return 1;
    }

    /**
     * @return array
     */
    public function getAssociatedGcProducts(Mage_Catalog_Model_Product $product)
    {
        if (!($product->getTypeInstance() instanceof
            Mage_Catalog_Model_Product_Type_Configurable)
        ) {
            throw new LogicException('Product is not of type configurable.');
        }

        /** @var $typeConfig Mage_Catalog_Model_Product_Type_Configurable */
        $typeConfig = $product->getTypeInstance();
        $products = $typeConfig->getUsedProducts(
            array($this->getGcProductIdAttributeId())
        );

        $gcProductIds = array();
        foreach ($products as $product) {
            $ids = json_decode($product->getGcProductIds(), true);
            $gcProductIds[] = array('id' => $ids[0]);
        }
        return $gcProductIds;
    }

    private function getGcProductIdAttributeId()
    {
        $this->initGcProductIdAttribute();
        return $this->gcProductIdAttribute->getId();
    }

    private function initGcProductIdAttribute()
    {
        if ($this->gcProductIdAttribute === null) {
            $this->gcProductIdAttribute
                = Mage::getModel('catalog/entity_attribute');
            $this->gcProductIdAttribute->loadByCode(
                'catalog_product', 'gc_product_ids'
            );
        }
    }
}
