<?php

class GoodsCloud_Sync_Model_Api
{
    /**
     * @var Goodscloud
     */
    private $api;

    /**
     * get the api object from the factory
     */
    public function __construct()
    {
        $factory = Mage::getModel('goodscloud_sync/api_factory');
        $this->api = $factory->getApi();
    }

    /**
     * return own company object
     *
     * @return GoodsCloud_Sync_Model_Api_Company
     */
    public function getCompany()
    {
        return $this->get('company')->getFirstItem();
    }

    /**
     * get all channels
     *
     * @return GoodsCloud_Sync_Model_Api_Channel_Collection
     */
    public function getChannels()
    {
        return $this->get('channel');
    }

    /**
     * get all categories
     *
     * @return GoodsCloud_Sync_Model_Api_Category_Collection
     */
    public function getCategories()
    {
        return $this->get('category');
    }

    /**
     * @return GoodsCloud_Sync_Model_Api_Property_Set_Collection
     */
    public function getPropertySets()
    {
        return $this->get('property_set');
    }

    /**
     * @return GoodsCloud_Sync_Model_Api_Property_Schema_Collection
     */
    public function getPropertySchemas()
    {
        return $this->get('property_schema');
    }

    /**
     * @return GoodsCloud_Sync_Model_Api_Company_Product_Collection
     */
    public function getCompanyProducts()
    {
        return $this->get('company_product');
    }

    /**
     * @param $id company product id to delete
     *
     * @return bool|string
     */
    public function deleteCompanyProduct($id)
    {
        return $this->delete('company_product', $id);
    }

    /**
     * @param int $id category id to delete
     *
     * @return bool|string
     */
    public function deleteCategory($id)
    {
        return $this->delete('category', $id);
    }

    /**
     * @param string $resource resource to delete
     * @param int    $id       id to delete
     *
     * @return bool|string
     */
    private function delete($resource, $id)
    {
        Mage::log("DELETE $resource with ID $id", Zend_Log::DEBUG, 'goodscloud.log');
        return $this->api->delete("/api/internal/$resource/$id");
    }

    /**
     * @param string $model name of the resource which is requested
     *
     * @return Varien_Data_Collection collection with items from api
     *
     * @throws Exception
     */
    private function get($model)
    {
        Mage::log("GET $model", Zend_Log::DEBUG, 'goodscloud.log');
        $response = $this->api->get("/api/internal/$model");
        Mage::log($response, Zend_Log::DEBUG, 'goodscloud.log');
        /* @var $collection Varien_Data_Collection */
        $collection = Mage::getModel('goodscloud_sync/api_' . $model . '_collection');
        foreach ($response->objects as $objects) {
            /* @var $item Varien_Object */
            $item = Mage::getModel('goodscloud_sync/api_' . $model);
            $collection->addItem($item->setData(get_object_vars($objects)));
        }

        return $collection;
    }

    /**
     * @param string   $name
     * @param int      $store
     * @param bool     $isDiscount
     * @param string[] $countryList
     *
     * @return GoodsCloud_Sync_Model_Api_Price_List
     * @throws GoodsCloud_Sync_Model_Api_Exception_IntegrityError
     */
    public function createPriceList($name, $store, $isDiscount, $countryList)
    {
        /** @var $apiHelper GoodsCloud_Sync_Helper_Api */
        $apiHelper = Mage::helper('goodscloud_sync/api');

        $data = array(
            //    id	column	Integer	not NULL Primary key.
            //    channel_products	relationship	List of ChannelProduct entries. Write-only, value not returned in API responses.
            //    discounted_channel_products	relationship	List of ChannelProduct entries. Write-only, value not returned in API responses.
            //    prices	relationship	List of Price entries. Write-only, value not returned in API responses. Cascade delete, delete-orphan.
            //    source_channels	relationship	List of Channel entries.
            //    target_channels	relationship	List of Channel entries.
            //    currency_code	column	UppercaseEnum The currency this price is denominated in. Must be ISO-3166 codes
            'currency_code'            => Mage::app()->getStore($store)->getCurrentCurrencyCode(),
            //    direction	column	Enum	not NULL	outgoing Allowed values incoming, outgoing
            'direction'                => 'outgoing',
            //    end_date	column	DateTime ISO format datetime with timezone offset: 1997-07-16T19:20:30.45+01:00. The time when this price list becomes inactive.
            //    external_identifier	column	String 256 characters or less. The identifier of this price list in an external system.
            'external_identifier'      => 'magento_default',
            //    external_source_channel	column	String 256 characters or less. The identifier for a supplier that is external to GoodsCloud.
            //    is_discount	column	Boolean	not NULL	False	 Is this a discount or a normal price?
            'is_discount'              => $isDiscount,
            //    label	column	String	not NULL 256 characters or less.  The name of this price list.
            'label'                    => $name,
            //    source_of_truth	column	Enum	not NULL	net Allowed values net, gross
            'source_of_truth'          => $apiHelper->getSourceOfTruth(),
            //    start_date	column	DateTime	not NULL ISO format datetime with timezone offset: 1997-07-16T19:20:30.45+01:00. The time when this price list becomes active.
            //    target_country_code_list	column	country_type_array List of codes for the country that this price is active in. Should be ISO-3166 codes
            'target_country_code_list' => $countryList,
            //    target_region_code_list	column	ARRAY of String 256 characters or less. List of codes for the state/province/region that this price is active in.
            //    updated	column	DateTime	not NULL ISO format datetime with timezone offset: 1997-07-16T19:20:30.45+01:00. The time when this row was last updated. Read-only.
            //    version	column	Integer	not NULL	1 Current version number of this entry, incremented each time it is changed. Read-only.
            //    company_id	column	Integer	not NULL ForeignKey('company.id') ON DELETE RESTRICT The company that owns this price list.
            'company_id'               => $apiHelper->getCompanyId(),
            //    company	relationship	Single Company entry. Write-only, value not returned in API responses.
            //    created	hybrid_property	The time when this row was created. Determined by looking in the history for this table. Read-only.
        );

        return $this->putPost('price_list', $data);
    }

    /**
     * @return GoodsCloud_Sync_Model_Api_Vat_Rate
     * @throws GoodsCloud_Sync_Model_Api_Exception_IntegrityError
     */
    public function createVatRate()
    {

        /** @var $apiHelper GoodsCloud_Sync_Helper_Api */
        $apiHelper = Mage::helper('goodscloud_sync/api');

        $data = array(
            //id	column	Integer	not NULL Primary key.
            //channel_vat_rates	relationship	List of ChannelVatRate entries. Cascade delete, delete-orphan.
            //label	column	String	not NULL 256 characters or less.
            'label'      => 'Magento VAT Rate',
            //rate	column	Numeric			 00.00000000
            //updated	column	DateTime	not NULL ISO format datetime with timezone offset: 1997-07-16T19:20:30.45+01:00. The time when this row was last updated. Read-only.
            //version	column	Integer	not NULL	1	Current version number of this entry, incremented each time it is changed. Read-only.
            //audit_user_id	column	Integer			ForeignKey('company_user.id') ON DELETE None ID of the user responsible for the last change of this object
            //company_id	column	Integer	not NULL		ForeignKey('company.id') ON DELETE CASCADE
            'company_id' => $apiHelper->getCompanyId(),
            //company	relationship	Single Company entry. created	hybrid_property The time when this row was created. Determined by looking in the history for this table. Read-only.
        );

        return $this->putPost('vat_rate', $data);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return GoodsCloud_Sync_Model_Api_Company_Product
     * @throws GoodsCloud_Sync_Model_Api_Exception_IntegrityError
     */
    public function createCompanyProduct(Mage_Catalog_Model_Product $product)
    {

        /** @var $apiHelper GoodsCloud_Sync_Helper_Api */
        $apiHelper = Mage::helper('goodscloud_sync/api');
        $data = array(
            //    id	column	Integer	not NULL Primary key.
            //    available_descriptions	relationship	List of ProductDescription entries.
            'available_descriptions' => $apiHelper->createDescriptions($product),
            //    available_images	relationship	List of ProductImage entries.
            'available_images'       => $apiHelper->createImages($product),
            //    channel_products	relationship	List of ChannelProduct entries. Cascade delete, delete-orphan.
            //    company_product_views	relationship	List of CompanyProductView entries.
            //    inventory_agreements	relationship	List of SourceAgreement entries. Cascade delete, delete-orphan.
            //    prices	relationship	List of Price entries. Cascade delete, delete-orphan.
            'prices'                 => $apiHelper->createPrices($product),
            //    sales_agreements	relationship	List of SourceAgreement entries. Cascade delete, delete-orphan.
            //    active	column	Boolean	not NULL	True Whether or not this company product is currently active
            'active'                 => $product->getStatus() === Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
            //    atp	column	Integer	not NULL The quantity of this product that is "available to promise". Sum of atp_internal and atp_external. Read-only.
            //    atp_external	column	Integer	not NULL The quantity of this product from external companies that is "available to promise". Read-only.
            //    atp_internal	column	Integer	not NULL The quantity of this product in this company that is "available to promise". Read-only.
            //    country_of_origin	column	UppercaseEnum Country of manufacture, production, or growth. Cf. the Wikipedia article. Should be ISO-3166 codes.
            //    dimensions	column	JSON	not NULL	{} A JSON object.    Indisputable facts about this product like length, weight, and intrastat codes.
            //    gtin	column	String	not NULL 14 characters or less. GTIN-8, GTIN-12, GTIN-13 or GTIN-14, see Wikipedia. All GTINs will be converted to GTIN-14s before insertion, so reading this field will always return a GTIN-14. Alternatively, EAN or UPC can be provided. See these attributes for details.
            //    label	column	String	not NULL 256 characters or less. A short name for this company product.
            'label'                  => substr($product->getName(), 0, 256),
            //    manufacturer_code	column	String 256 characters or less. Unique code used by the manufacturer for this product.
            //    manufacturer_name	column	String 256 characters or less. Name of the manufacturer.
            'manufacturer_name'      => $product->getAttributeText('manufacturer'),
            //    physical	column	Boolean		True False means virtual product
            'physical'               => $apiHelper->isPhysical($product),
            //    physical_quantity	column	Integer	not NULL The physical quantity of this product in this company. Read-only.
            //    properties	column	JSON	not NULL	{} A JSON object.
            //    customer group, gender, date of birth, list of IP addresses, etc.
            //    stocked	column	Boolean		True False means never out of stock: manufactured on demand or virtual
            'stocked'                => (bool)$product->getStockItem()->getManageStock(),
            //    stocked_quantity	column	Integer	not NULL The total physical quantity of this product in this company that is stocked in storage cells. Read-only.
            //    updated	column	DateTime	not NULL ISO format datetime with timezone offset: 1997-07-16T19:20:30.45+01:00. The time when this row was last updated. Read-only.
            //    version	column	Integer	not NULL	1 Current version number of this entry, incremented each time it is changed. Read-only.
            //    abstract_product_id	column	Integer	not NULL ForeignKey('abstract_product.id') ON DELETE RESTRICT abstract_product	relationship	Single AbstractProduct entry.
            //    company_id	column	Integer	not NULL ForeignKey('company.id') ON DELETE RESTRICT company	relationship	Single Company entry.
            'company_id'             => $apiHelper->getCompanyId(),
            //    created	hybrid_property The time when this row was created. Determined by looking in the history for this table. Read-only.
            //    ean	hybrid_property The EAN representation of the underlying GTIN value. None if conversion is not possible. Supported formats: EAN-8, EAN-13
            //    upc	hybrid_property The UPC-A representation of the underlying GTIN value. None if conversion is not possible.
        );

        // depending on what identifier exists we set different keys
        $data[$apiHelper->getIdentifierType()] = $product->getData($apiHelper->getIdentifierAttribute());

        return $this->putPost('company_product', $data);
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Core_Model_Store      $store
     *
     * @return GoodsCloud_Sync_Model_Api_Channel_Product
     * @throws GoodsCloud_Sync_Model_Api_Exception_IntegrityError
     */
    public function createChannelProduct(Mage_Catalog_Model_Product $product, Mage_Core_Model_Store $store)
    {

        /** @var $apiHelper GoodsCloud_Sync_Helper_Api */
        $apiHelper = Mage::helper('goodscloud_sync/api');
        $data = array(
            //    id	column	Integer	not NULL Primary key.
            //    logistic_order_items	relationship	List of LogisticOrderItem entries. Write-only, value not returned in API responses.
            //    logistic_return_items	relationship	List of LogisticReturnItem entries. Write-only, value not returned in API responses.
            //    order_items	relationship	List of OrderItem entries. Write-only, value not returned in API responses.
            //    storage_cell_inventories	relationship	List of StorageCellInventory entries. Cascade delete, delete-orphan.
            //    active	column	Boolean	not NULL	True Whether or not this channel product is currently active
            'active'             => $product->getStatus() === Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
            //    atp	column	Integer	not NULL The quantity of this product in this channel that is "available to promise". Read-only.
            //    notify_quantity	column	Integer	not NULL	0 The quantity at which to send a notification about low inventory.
            //    packaging_unit	column	Integer			 Smallest number of this article that is sold by this channel physical_quantity	column	Integer	not NULL The total physical quantity of this product in this channel. Read-only.
            //    properties	column	JSON	not NULL	{} A JSON object.
            'properties'         => '', // TODO
            //    reserved_quantity	column	Integer	not NUL The quantity of the product in this channel that is reserved for presales or replacements Read-only.
            //    safety_quantity	column	Integer	not NULL	0 The quantity of this product that must always be kept in stock, e.g. for photos to be taken, or as a buffer.
            //    sku	column	String 256 characters or less. The SKU (stock-keeping unit) used to track this product in this channel.
            'sku'                => $product->getSku(),
            //    sold_quantity	column	Integer	not NULL The quantity of the product in this channel that is sold but has not yet been removed from storage cells. Read-only.
            //    stocked_quantity	column	Integer	not NULL The total physical quantity of this product in this channel that is stocked in storage cells. Read-only.
            //    updated	column	DateTime	not NULL ISO format datetime with timezone offset: 1997-07-16T19:20:30.45+01:00. The time when this row was last updated. Read-only.
            //    version	column	Integer	not NULL	1	 Current version number of this entry, incremented each time it is changed. Read-only.
            //    audit_user_id	column	Integer			 ForeignKey('company_user.id') ON DELETE None ID of the user responsible for the last change of this object
            //    channel_id	column	Integer	not NULL ForeignKey('channel.id') ON DELETE RESTRICT
            'channel_id'         => $apiHelper->getChannelId($store),
            //    channel	relationship	Single Channel entry.
            //    chosen_description_id	column	Integer ForeignKey('product_description.id') ON DELETE SET NULL
            //    chosen_description	relationship	Single ProductDescription entry.
            //    company_product_id	column	Integer	not NULL ForeignKey('company_product.id') ON DELETE CASCADE
            'company_product_id' => $apiHelper->getCompanyProductId($product),
            //    company_product	relationship	Single CompanyProduct entry.
            //    discount_price_list_id	column	Integer ForeignKey('price_list.id') ON DELETE SET NULL
            //    discount_price_list	relationship	Single PriceList entry.
            //    price_list_id	column	Integer ForeignKey('price_list.id') ON DELETE SET NULL
            'price_list_id'      => $apiHelper->getDefaultPriceListId(),
            //    price_list	relationship	Single PriceList entry.
            //    property_set_id	column	Integer ForeignKey('property_set.id') ON DELETE SET NULL
            'property_set_id'    => $apiHelper->getPropertySetId($product),
            //    property_set	relationship	Single PropertySet entry.
            //    created	hybrid_property The time when this row was created. Determined by looking in the history for this table. Read-only.
            //    chosen_images	relationship	List of ProductImage entries.
        );

        return $this->putPost('channel_product', $data);
    }

    /**
     * @param Mage_Core_Model_Store $view storeview to create channel from
     *
     * @return GoodsCloud_Sync_Model_Api_Channel
     */
    public function createChannel(Mage_Core_Model_Store $view)
    {
        /** @var $helper GoodsCloud_Sync_Helper_Data */
        $helper = Mage::helper('goodscloud_sync');

        $data = array(
            // 'id'	// column	Integer	not NULL Primary key.
            'currency_code'       => $helper->getCurrencyByStoreView($view),

            // column	UppercaseEnum	not NULL The default currency for this channel. Must be ISO-4217 currency code
            'external_identifier' => $view->getId(), // column	String 256 characters or less .

            // 'is_inventory'	// column	Boolean	not null	false Is this channel an inventory channel ? Read - only, except when creating new objects .

            // column	Boolean	not null	false Is this channel a sales channel ? Read - only, except when creating new objects .
            'is_sales'            => true,

            // column	String	not null 256 characters or less .
            'label'               => $helper->getChannelNameByStoreView($view),

            //	column	LowercaseEnum	not null The default language for this channel . Must be {ISO - 639} codes
            'language_code'       => $helper->getLanguageByStoreView($view)

            // notification_emails	column	ARRAY of String		[] 256 characters or less . List of email addresses to notify of new [Logistic]Orders .
            // quality_score	column	Numeric	not null	0 0.000000000 Quality score calculated by GoodsCloud . Read - only .
            // return_reasons	column	ARRAY of String	not null	['other'] 256 characters or less .     List of reasons for why an item was returned . There is always at least an 'other' reason . This list is also defined, though not used, for         inventory channels .
            // updated    column    DateTime	not null ISO format datetime with timezone offset: 1997 - 07 - 16T19:20:30.45 + 01:00. The time when this row was last updated . Read - only .
            // version	column	Integer	not null	1 Current version number of this entry, incremented each time it is changed . Read - only .
            // company_id	column	Integer	not null ForeignKey('company.id') ON DELETE RESTRICT company	relationship	Single Company entry .
            // email_config_id	column	Integer ForeignKey('email_config.id') ON DELETE SET null
            // email_config	relationship	Single EmailConfig entry . Write - only, value not returned in API responses .
            // created	hybrid_property The time when this row was created . Determined by looking in the history for this table . Read - only .
        );

        return $this->putPost('channel', $data);
    }

    /**
     * @param Mage_Eav_Model_Entity_Attribute_Set $set
     * @param Mage_Core_Model_Store               $view
     *
     * @return GoodsCloud_Sync_Model_Api_Property_Set
     *
     * @throws GoodsCloud_Sync_Model_Api_Exception_IntegrityError
     * @throws Mage_Core_Exception
     */
    public function createPropertySet(
        Mage_Eav_Model_Entity_Attribute_Set $set,
        Mage_Core_Model_Store $view
    ) {
        if (!$view->getGcChannelId()) {
            Mage::throwException('StoreView has no associated channel!');
        }
        $data = array(
            // id	column	Integer	not NULL Primary key.
            // channel_product_views	relationship	List of ChannelProductView entries. Write-only, value not returned in API responses.
            // channel_products	relationship	List of ChannelProduct entries. Write-only, value not returned in API responses.
            // optional_properties	relationship	List of PropertySchema entries.
            // required_properties	relationship	List of PropertySchema entries.
            // description	column	Text	Any length allowed.
            'description'         => '',
            // external_identifier	column	String 256 characters or less.
            'external_identifier' => $set->getId(),
            // label	column	String	not NULL	 256 characters or less.
            'label'               => $set->getAttributeSetName(),
            // channel_id	column	Integer	not NULL ForeignKey('channel.id') ON DELETE CASCADE
            'channel_id'          => $view->getGcChannelId(),
            // channel	relationship	Single Channel entry. Write-only, value not returned in API responses.
        );

        $response = $this->putPost('property_set', $data);
        return $response;
    }

    /**
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @param Mage_Core_Model_Store           $view
     *
     * @return GoodsCloud_Sync_Model_Api_Property_Schema
     *
     * @throws GoodsCloud_Sync_Model_Api_Exception_IntegrityError
     * @throws Mage_Core_Exception
     */
    public function createPropertySchema(Mage_Eav_Model_Entity_Attribute $attribute, Mage_Core_Model_Store $view)
    {
        if (!$view->getGcChannelId()) {
            Mage::throwException('StoreView has no associated channel!');
        }
        $helper = Mage::helper('goodscloud_sync/api');

        $data = array(
            // id	column	Integer	not NULL	 Primary key.
            // abstract_properties	relationship	List of AbstractProperty entries.
            // optional_property_sets	relationship	List of PropertySet entries.
            // required_property_sets	relationship	List of PropertySet entries.
            // comparable	column	Boolean		False default	column	String 256 characters or less.
            'comparable'          => $attribute->getIsComparable(),
            // description	column	Text Any length allowed.
            'description'         => '',
            // external_identifier	column	String 256 characters or less.
            'external_identifier' => $attribute->getId(),
            // filterable	column	Boolean		False label	column	String	not NULL 256 characters or less.
            'filterable'          => $attribute->getIsFilterable() || $attribute->getIsFilterableInSearch(),
            // max	column	Numeric 0000000000000000.0000000000000000 min	column	Numeric 0000000000000000.0000000000000000
            // label	column	String	not NULL 256 characters or less.
            'label'               => $attribute->getName(),
            // multivalue	column	Boolean	not NULL	False
            'multivalue'          => $helper->isAttributeMultiValue($attribute), // TODO
            // searchable	column	Boolean		True
            'searchable'          => $attribute->getIsSearchable(),
            // type	column	Enum	not NULL	free Allowed values free, enum, range, bool, datetime
            'type'                => $helper->getPropertySchemaTypeForAttribute($attribute),
            // units	column	String 16 characters or less.
            // values	column	ARRAY of String 256 characters or less.
            'values'              => $helper->getPropertySchemaValuesForAttribute($attribute, $view),
            // visible	column	Boolean		True
            'visible'             => $attribute->getIsVisibleOnFront(),
            // channel_id	column	Integer	not NULL ForeignKey('channel.id') ON DELETE CASCADE
            'channel_id'          => $view->getGcChannelId(),
            // channel	relationship	Single Channel entry.
        );

        return $this->putPost('property_schema', $data);
    }

    /**
     * @param Mage_Catalog_Model_Category $category
     * @param Mage_Core_Model_Store       $store
     * @param int                         $gcParentId
     *
     * @return GoodsCloud_Sync_Model_Api_Category
     */
    public function createCategory(Mage_Catalog_Model_Category $category, Mage_Core_Model_Store $store, $gcParentId)
    {
        $data = array(
            //        id	column	Integer	not NULL Primary key.
            //        children	relationship	List of Category entries. Cascade delete, delete-orphan.
            //        active	column	Boolean		True
            'active'              => $category->getIsActive(),
            //        external_identifier	column	String 256 characters or less.
            'external_identifier' => $category->getId(),
            //        label	column	String	not NULL 256 characters or less.
            'label'               => $category->getName(),
            //        position	column	Integer Position of this category in the list of categories in its parent category.
            'position'            => $category->getPosition(),
            //        selectable	column	Boolean	not NULL	True True means this category can contain products.
            'selectable'          => $category->getIsAnchor(),
            //        abstract_category_id	column	Integer ForeignKey('abstract_category.id') ON DELETE SET NULL
            //        abstract_category	relationship	Single AbstractCategory entry.
            //        channel_id	column	Integer	not NULL ForeignKey('channel.id') ON DELETE CASCADE
            'channel_id'          => $store->getGcChannelId(),
            //        channel	relationship	Single Channel entry. Write-only, value not returned in API responses.
            //        parent_id	column	Integer ForeignKey('category.id') ON DELETE CASCADE
            'parent_id'           => $gcParentId,
            //        parent	relationship	Single Category entry.
            //        channel_product_views	relationship	List of ChannelProductView entries.
        );

        return $this->putPost('category', $data);
    }

    /**
     * @param array $requiredPropertySchemaIds
     * @param array $optionalPropertySchemaIds
     * @param int   $propertySetId
     *
     * @internal param int[] $propertySchemaIds
     */
    public function mapPropertySchema2PropertySet(
        array $requiredPropertySchemaIds, array $optionalPropertySchemaIds, $propertySetId
    ) {
        $data = array(
            'id'                  => $propertySetId,
            'optional_properties' => $optionalPropertySchemaIds,
            'required_properties' => $requiredPropertySchemaIds,
        );

        $this->putPost('property_set', $data);
    }

    /**
     * @param string $resource resource to send data to
     * @param array  $data     data to send
     *
     * @return string data of the created/updated
     *
     * @throws GoodsCloud_Sync_Model_Api_Exception_IntegrityError
     */
    private function putPost($resource, array $data)
    {
        try {
            if (isset($data['id'])) {
                Mage::log("PUT . $resource", Zend_Log::DEBUG, 'goodscloud.log');
                Mage::log($data, Zend_Log::DEBUG, 'goodscloud.log');
                $response = $this->api->put('/api/internal/' . $resource, array(), $data);
            } else {
                Mage::log("POST . $resource", Zend_Log::DEBUG, 'goodscloud.log');
                Mage::log($data, Zend_Log::DEBUG, 'goodscloud.log');
                $response = $this->api->post('/api/internal/' . $resource, array(), $data);
            }
            Mage::log('RESPONSE', Zend_Log::DEBUG, 'goodscloud.log');
            Mage::log($response, Zend_Log::DEBUG, 'goodscloud.log');

            $item = Mage::getModel('goodscloud_sync/api_' . $resource);
            $item->setData(get_object_vars($response));
            return $item;

        } catch (Exception $e) {
            throw $this->parseErrorMessage($e);
        }
    }

    /**
     * @param Exception $exception
     *
     * @return GoodsCloud_Sync_Model_Api_Exception_IntegrityError
     */
    private function parseErrorMessage(Exception $exception)
    {
        $message = $exception->getMessage();

        // IntegrityError:
        //        API request failed (status code 400): (IntegrityError) duplicate key value violates unique constraint "channel_label_company_id_key"
        //DETAIL:  Key (label, company_id)=(Default Store Viewasd, 24) already exists.
        //    'INSERT INTO channel (quality_score, label, external_identifier, is_sales, is_inventory, currency_code, language_code, company_id, email_config_id, return_reasons, cancellation_reasons, notification_emails, version) VALUES (%(quality_score)s, %(label)s, %(external_identifier)s, %(is_sales)s, %(is_inventory)s, %(currency_code)s, %(language_code)s, %(company_id)s, %(email_config_id)s, %(return_reasons)s, %(cancellation_reasons)s, %(notification_emails)s, %(version)s) RETURNING channel.id' {'email_config_id': None, 'is_inventory': False, 'external_identifier': u'1', 'company_id': 24, 'quality_score': 0, 'return_reasons': ['other'], 'version': 1, 'is_sales': True, 'language_code': u'en', 'notification_emails': [], 'label': u'Default Store Viewasd', 'currency_code': u'EUR', 'cancellation_reasons': ['consumer', 'test order', 'stock error']}
        if (preg_match('#API .* \(status code (\d*)\): \((.*)\) (.*)\nDETAIL:  (.*)\n(.*)#', $message, $matches)) {
            if ($matches[2] == 'IntegrityError') {
                $exception = new GoodsCloud_Sync_Model_Api_Exception_IntegrityError(
                    $matches[3] . "\n" . $matches[4], $matches[1], $exception
                );
                $exception->setDetails($matches[4]);
                $exception->setLongDetails($matches[5]);
                return $exception;
            }
        } elseif (preg_match('#API .* \(status code (\d*)\): \((.*?)\) (.*)#', $message, $matches)) {
            // API request failed (status code 400): (ProgrammingError) can't adapt type 'dict' 'INSERT INTO property_schema (label, external_identifier, description, channel_id, type, values, multivalue, "default", units, min, max, visible, searchable, filterable, comparable) VALUES (%(label)s, %(external_identifier)s, %(description)s, %(channel_id)s, %(type)s, %(values)s, %(multivalue)s, %(default)s, %(units)s, %(min)s, %(max)s, %(visible)s, %(searchable)s, %(filterable)s, %(comparable)s) RETURNING property_schema.id' {'comparable': u'1', 'description': u'', 'searchable': u'1', 'min': None, 'default': None, 'max': None, 'external_identifier': u'92', 'visible': u'0', 'label': u'color', 'channel_id': u'126', 'multivalue': False, 'units': None, 'values': [{u'value': u'', u'label': u''}], 'type': u'enum', 'filterable': True}
            if ($matches[2] == 'ProgrammingError') {
                $exception = new GoodsCloud_Sync_Model_Api_Exception_ProgrammingError(
                    $matches[2] . "\n" . $matches[3], $matches[1], $exception
                );
                $exception->setDetails($matches[3]);
                return $exception;
            }
        }

        Mage::throwException('Unknown Error: ' . $message);
    }
}
