<?php

/**
 * Class GoodsCloud_Sync_Model_Api_Order
 *
 * @method int getId()
 * @method array getBillingAddress()
 * @method array getBillingTelephone()
 * @method array getCreditNotes()
 * @method array getInvoices()
 * @method array getOrderItems()
 * @method array getReplacedOrderReturnItems()
 * @method array getReturns()
 * @method array getShipments()
 * @method array getShippingAddress()
 * @method array getShippingTelephone()
 * @method array getSubPayIns()
 * @method array getSubPayOuts()
 * @method bool getAwaitsRouting()
 * @method string getCurrencyCode()
 * @method string getExternalIdentifier()
 * @method array getExtra()
 * @method bool getPayLater()
 * @method string getPlaced()
 * @method string getSource()
 * @method string getUpdated()
 * @method int getVersion()
 * @method int getAuditUserId()
 * @method int getChannelId()
 * @method array getChannel()
 * @method int getConsumerId()
 * @method array getConsumer()
 * @method array getCreated()
 * @method array getDeliveryStatus()
 * @method array getPackingStatus()
 * @method array getPayInStatus()
 * @method array getPayOutStatus()
 * @method array getProgress()
 * @method array getRoutingStatus()
 * @method bool getShippable()
 * @method float getTotalGross()
 * @method float getTotalNet()
 * @method float getTotalPaidIn()
 * @method float getTotalPaidOut()
 * @method array getItems()
 * @method array getTotals()
 */
class GoodsCloud_Sync_Model_Api_Order extends Varien_Object
{

    /**
     * @param array|string $key
     * @param null         $value
     *
     * @return Varien_Object|void
     */
    public function setData($key, $value = null)
    {
        parent::setData($key, $value);

        if ($value === null) {
            $this->indexOrderItems();
        }

        return $this;
    }

    private function indexArrayKey($key)
    {
        $items = array();
        foreach ($this->getDataUsingMethod($key) as $item) {
            $items[$item['id']] = $item;
        }
        $this->setData($key, $items);
    }

    /**
     *
     */
    private function indexOrderItems()
    {
        $this->indexArrayKey('order_items');
    }
}
