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
        return $this->getGcConsumerIdAndCreateIfNeeded($order);
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
        } else {
            // use the data from the order to create one
            $gcConsumerId = $this->getGoodscloudConsumerIdByEmail($order->getCustomerEmail());
            if (!$gcConsumerId) {
                $gcConsumerId = $this->createGcConsumerFromOrder($order)->getId();
            }
        }
        return $gcConsumerId;
    }

    private function createGcConsumerFromOrder(Mage_Sales_Model_Order $order)
    {
        $attributes = array(
            'email',
            'firstname',
            'lastname',
            'prefix',
            'middlename',
            'dob',
            'gender',
            'suffix',
        );
        $customer = Mage::getModel('customer/customer');
        foreach ($attributes as $attribute) {
            $customer->setDataUsingMethod($attribute, $order->getDataUsingMethod('customer_' . $attribute));
        }

        return $this->createGcConsumer($customer);
    }

    /**
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return GoodsCloud_Sync_Model_Api_Consumer
     */
    private function createGcConsumer(Mage_Customer_Model_Customer $customer)
    {
        return $this->api->createConsumer($customer);
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