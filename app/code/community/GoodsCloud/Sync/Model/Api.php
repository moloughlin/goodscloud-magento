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


    public function createChannel(Mage_Core_Model_Store $view)
    {
        /** @var $helper GoodsCloud_Sync_Helper_Data */
        $helper = Mage::helper('goodscloud_sync');

        $data = array(
            // 'id'	// column	Integer	not NULL Primary key.
            'currency_code'       => $helper->getCurrencyByStoreView($view),
            // column	UppercaseEnum	not NULL The default currency for this channel. Must be ISO-4217 currency code
            'external_identifier' => $view->getId(), // column	String 256 characters or less .
            // TODO is_inventory?
            // 'is_inventory'	// column	Boolean	not null	false Is this channel an inventory channel ? Read - only, except when creating new objects .
            'is_sales'            => true,
            // column	Boolean	not null	false Is this channel a sales channel ? Read - only, except when creating new objects .
            // TODO prefix might be a good idea, or just magento?
            'label'               => $view->getName(), // column	String	not null 256 characters or less .
            'language_code'       => $helper->getLanguageByStoreView($view)
            //	column	LowercaseEnum	not null The default language for this channel . Must be {ISO - 639} codes
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

    private function putPost($resource, $data)
    {
        return $this->api->post('/api/internal/' . $resource, array(), $data);
    }
}


