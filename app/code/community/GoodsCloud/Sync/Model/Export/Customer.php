<?php

class GoodsCloud_Sync_Model_Export_Customer
{
    const MAX_EMAIL_LENGTH = 256;
    const MAX_FIRSTNAME_LENGTH = 256;
    const MAX_LASTNAME_LENGTH = 256;
    const MAX_LENGTH_ORGA_NAME = 256;
    const MAX_LENGTH_PREFIX = 256;
    const MAX_LENGTH_SUFFIX = 256;
    /**
     * @var GoodsCloud_Sync_Model_Api
     */
    private $api;

    public function exportByOrder(Mage_Sales_Model_Order $order)
    {
        $gcCustomerId = $this->getGcConsumerIdAndCreateIfNeeded($order);
        return $gcCustomerId;
    }

    /**
     * @param int|Mage_Customer_Model_Customer $customer id or customer object
     */
    public function export($customer)
    {

    }

    /**
     * @param string $email customer email
     *
     * @return int|false
     */
    private function getGoodscloudConsumerIdByEmail($email)
    {
        $consumer = $this->api->getConsumerByEmail($email);
        if ($consumer instanceof GoodsCloud_Sync_Model_Api_Consumer) {
            return $consumer->getId();
        }
        return false;
    }

    /**
     * @param GoodsCloud_Sync_Model_Api $api
     */
    public function setApi(GoodsCloud_Sync_Model_Api $api)
    {
        $this->api = $api;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return int
     */
    private function getGcConsumerIdAndCreateIfNeeded(Mage_Sales_Model_Order $order)
    {
        if (($customerId = $order->getCustomerId())) {
            // customer is registered, so check whether to create one and if needed create
            $customer = Mage::getModel('customer/customer')->load($customerId);
            if (!($gcConsumerId = $customer->getGcConsumerId())) {
                $gcConsumerId = $this->getGoodscloudConsumerIdByEmail($customer->getEmail());
                if (!$gcConsumerId) {
                    $gcConsumer = $this->createGcConsumer($customer);
                    $customer->setGcConsumerId($gcConsumer->getId())->save();
                }
            }

            return $gcConsumerId;
        } else {
            // use the data from the order to create one
        }
    }

    private function createGcConsumer(Mage_Customer_Model_Customer $customer)
    {
        $apiHelper = Mage::helper('goodscloud_sync/api');
        $data = array(
            //    id	column	Integer	not null	Primary key .
            //    invoices	relationship	List of Invoice entries .
            //    orders	relationship	List of Order entries .
            //    pay_ins	relationship	List of PayIn entries .
            //    pay_outs	relationship	List of PayOut entries .
            //    email	column	String	not null		256 characters or less .
            'email'               => $this->sanitizeEmail($customer->getEmail()),
            //    external_identifier	column	String	256 characters or less .
            'external_identifier' => $customer->getId(),
            //    first_name	column	String			256 characters or less .
            'first_name'          => $this->sanitizeFirstname($customer->getFirstname()),
            //    language_code	column	LowercaseEnum	not null The language for this consumer . Must be {ISO - 639} codes
            'language_code'       => $apiHelper->getLanguage($customer->getStoreId()),
            //    last_name	column	String 256 characters or less .
            'last_name'           => $this->sanitizeLastname($customer->getLastname()),
            //    organization_name	column	String 256 characters or less .
            //    prefix	column	String			256 characters or less .
            'prefix'              => $this->sanitizePrefix($customer->getPrefix()),
            //    properties	column	JSON	not null	{} A JSON object . customer group, gender, date of birth, list of IP addresses, etc .
            'properties'          => array(
                'middlename'    => $customer->getMiddlename(),
                'group_id'      => $customer->getGroupId(),
                'date_of_birth' => $customer->getDob(),
                'gender'        => $customer->getGender(),
            ),
            //    suffix	column	String 256 characters or less .
            'suffix'              => $this->sanitizeSuffix($customer->getSuffix()),
            //    timezone_name	column	String		UTC 256 characters or less . The timezone for this consumer . Must be {tz} database time zone name(not offset!)
            //    updated	column	DateTime	not null ISO format datetime with timezone offset: 1997 - 07 - 16T19:20:30.45 + 01:00. The time when this row was last updated . Read - only .
            //    vat_number	column	String 256 characters or less .
            'vat_number'          => $customer->getTaxVat(),
            //    version	column	Integer	not null	1 Current version number of this entry, incremented each time it is changed . Read - only .
            //    audit_user_id	column	Integer ForeignKey('company_user.id') ON DELETE None ID of the user responsible for the last change {of} this object
            //    company_id	column	Integer	not null ForeignKey('company.id') ON DELETE RESTRICT
            'company_id'          => $apiHelper->getCompanyId(),
            //    company	relationship	Single Company entry .
            //    created	hybrid_property The time when this row was created . Determined by looking in the history for this table . Read - only .
        );

        return $this->api->createConsumer($data);

    }

    private function sanitizeEmail($email)
    {
        return substr($email, 0, self::MAX_EMAIL_LENGTH);
    }

    private function sanitizeFirstname($firstname)
    {
        return substr($firstname, 0, self::MAX_FIRSTNAME_LENGTH);
    }

    private function sanitizeLastname($lastname)
    {
        return substr($lastname, 0, self::MAX_LASTNAME_LENGTH);
    }

    private function sanitizeOrganisationName($orgaName)
    {
        return substr($orgaName, 0, self::MAX_LENGTH_ORGA_NAME);
    }

    private function sanitizePrefix($prefix)
    {
        return substr($prefix, 0, self::MAX_LENGTH_PREFIX);
    }

    private function sanitizeSuffix($suffix)
    {
        return substr($suffix, 0, self::MAX_LENGTH_SUFFIX);
    }
}
