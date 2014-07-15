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

    public function getChannels()
    {
        return $this->get('channel');
    }

    /**
     * @param string $model name of the resource which is requested
     *
     * @return Varien_Data_Collection collection with items from api
     * @throws Exception
     */
    private function get($model)
    {
        $response = $this->api->get("/api/internal/$model");
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
     * @param Mage_Core_Model_Store $view storeview to create channel from
     *
     * @return bool true on success, false on failure
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

        $response = $this->putPost('channel', $data);
        return $response;
    }

    public function createPropertySet(
        Mage_Eav_Model_Entity_Attribute_Set $set,
        Mage_Core_Model_Store $view
    ) {
        if(!$view->getGcChannelId()) {
            Mage::throwException('StoreView has no associated channel!');
        }
        $data = array(
            // id	column	Integer	not NULL Primary key.
            // channel_product_views	relationship	List of ChannelProductView entries. Write-only, value not returned in API responses.
            // channel_products	relationship	List of ChannelProduct entries. Write-only, value not returned in API responses.
            // optional_properties	relationship	List of PropertySchema entries.
            // required_properties	relationship	List of PropertySchema entries.
            // description	column	Text	Any length allowed.
            'description' => '',
            // external_identifier	column	String 256 characters or less.
            'external_identifier' => $set->getId(),
            // label	column	String	not NULL	 256 characters or less.
            'label' => $set->getAttributeSetName(),
            // channel_id	column	Integer	not NULL ForeignKey('channel.id') ON DELETE CASCADE
            'channel_id' => $view->getGcChannelId(),
            // channel	relationship	Single Channel entry. Write-only, value not returned in API responses.
        );

        $response = $this->putPost('property_set', $data);
        var_dump($response);
        return $response;
    }

    /**
     * @param string $resource resource to send data to
     * @param array  $data     data to send
     *
     * @return bool true on success, false on failure
     *
     * @throws GoodsCloud_Sync_Model_Api_Exception_IntegrityError
     */
    private function putPost($resource, array $data)
    {
        try {
            $response = $this->api->post('/api/internal/' . $resource, array(), $data);
            return $response;
        } catch (Exception $e) {
            $this->parseErrorMessage($e);
        }
    }

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
                throw $exception;
            }
        }
    }
}


