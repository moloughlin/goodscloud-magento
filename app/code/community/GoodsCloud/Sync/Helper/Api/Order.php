<?php

class GoodsCloud_Sync_Helper_Api_Order extends Mage_Core_Helper_Abstract
{
    const ROUTING_STATUS_ACTIVE = 'active';

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    public function getOrderItems(Mage_Sales_Model_Order $order)
    {
        $itemsForApiCall = array();
        foreach ($order->getAllItems() as $item) {
            $itemsForApiCall[] = $this->getOrderItem($item);
        }

        return $itemsForApiCall;
    }

    private function getOrderItem(Mage_Sales_Model_Order_Item $item)
    {

        $apiHelper = Mage::helper('goodscloud_sync/api');
        return array(
            //    id	column	Integer	not NULL	 Primary key.
            //    credit_note_items	relationship	List of CreditNoteItem entries.
            //    invoice_items	relationship	List of InvoiceItem entries.
            //    logistic_order_items	relationship	List of LogisticOrderItem entries.
            //    order_return_items	relationship	List of OrderReturnItem entries.
            //    cancellation_reason	column	String 256 characters or less.
            //    external_identifier	column	String 256 characters or less.
            'external_identifier' => $item->getId(),
            //    extra	column	JSON	not NULL	{} A JSON object. For storing extra information.
            //    gtin	column	String	not NULL 14 characters or less. GTIN-8, GTIN-12, GTIN-13 or GTIN-14, see Wikipedia. All GTINs will be converted to GTIN-14s before insertion, so reading this field will always return a GTIN-14. Alternatively, EAN or UPC can be provided. See these attributes for details.
            'gtin'                => $item->getProduct()->getDataUsingMethod($apiHelper->getIdentifierAttribute()),
            //    net	column	Numeric	not NULL 00000000.00 The original net price for quantity one of this item.
            'net'                 => $this->sanitizePrice($item->getBasePrice()),
            //    quantity	column	Integer	not NULL
            'quantity'            => (int)$this->sanitizeInt($item->getQtyOrdered()),
            //    routing_status	column	Enum	not NULL	active Allowed values draft, active, on hold, low stock, backorder, canceled Allowed transitions:
            //      START → active, draft
            //          draft → a, c, t, i, v, e
            //          active → on hold, low stock, backorder, canceled
            //          on hold → active, backorder, canceled
            //          low stock → active, on hold, backorder, canceled
            //          backorder → active, on hold, canceled
            //          draft: this order item can be deleted without cancellation. It will be excluded from all further processes involving its order, including inventory routing and invoice and credit note generation. To include the item in these processes, set its routing_status to active.
            //          active: this order item should be shipped normally
            //          on hold: this order item was paused manually, no action will be taken until it leaves this state
            //          low stock: this order item was not shippable from any inventory channel and paused by the inventory routing
            //          backorder: this order item was not found in any inventory channel; the sales channel is awaiting delivery of this order item
            //          canceled: this order item will not be shipped
            'routing_status'      => self::ROUTING_STATUS_ACTIVE,
            // TODO is this correct?
            //    total_net	column	Numeric	not NULL 00000000.00 The total net price for the total quantity of products in this item.
            'total_net'           => $this->sanitizePrice($item->getBaseRowTotal()),
            //    total_vat_amount	column	Numeric 00000000.00 The total VAT amount for the total quantity of all products in this item.
            'total_vat_amount'    => $this->sanitizePrice($item->getBaseTaxAmount()),
            //    updated	column	DateTime	not NULL ISO format datetime with timezone offset: 1997-07-16T19:20:30.45+01:00. The time when this row was last updated. Read-only.
            //    version	column	Integer	not NULL	1 Current version number of this entry, incremented each time it is changed. Read-only.
            //    audit_user_id	column	Integer ForeignKey('company_user.id') ON DELETE None ID of the user responsible for the last change of this object
            //    channel_product_id	column	Integer	not NULL ForeignKey('channel_product.id') ON DELETE RESTRICT
            //    channel_product	relationship	Single ChannelProduct entry.
            'channel_product'     => $apiHelper->getGcProductId($item->getProduct(), $item->getStoreId()),
            //    order_id	column	Integer	not NULL ForeignKey('order.id') ON DELETE CASCADE
            //    order	relationship	Single Order entry.
            //    related_order_item_id	column	Integer ForeignKey('order_item.id') ON DELETE RESTRICT
            //    related_order_item	relationship	Single OrderItem entry.
            //    vat_rate_id	column	Integer	not NULL ForeignKey('vat_rate.id') ON DELETE RESTRICT The VAT rate that was originally used for calculating the total VAT amount. This is purely for record-keeping and will not be used for calculations.
            'vat_rate_id'         => 48,
            // TODO
            //    vat_rate	relationship	Single VatRate entry.
            //    created	hybrid_property The time when this row was created. Determined by looking in the history for this table. Read-only.
            //    currency_code	hybrid_property The currency code that this item is denominated in. Must be ISO-4217 currency code.
            #'currency_code'       => $item->getOrder()->getBaseCurrencyCode()
            //    delivery_status	hybrid_property If there are no logistic_order_items or no shipments for any of those logistic_order_items, this has the special value N/A. If all logistic_order_items have shipments with the same delivery_status, this has the value of that common status. Otherwise, it has the special value mixed. Read-only.
            //    ean	hybrid_property The EAN representation of the underlying GTIN value. None if conversion is not possible. Supported formats: EAN-8, EAN-13
            //    packing_status	hybrid_property If there are no logistic_order_items, this has the special value N/A. If all logistic_order_items have the same packing_status, this has the value of that common status. Otherwise, it has the special value mixed. Read-only.
            //    total_gross	hybrid_property The total gross price for the total quantity of products in this item. Read-only.
            //    upc	hybrid_property The UPC-A representation of the underlying GTIN value. None if conversion is not possible.
            //    parent	property	Read-only.
        );
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     *
     * @todo sanitize all values
     */
    public function getBillingAddress(Mage_Sales_Model_Order $order)
    {
        $address = $order->getBillingAddress();

        return array(
            //    id	column	Integer	not NULL Primary key.
            //    city	column	String 256 characters or less.
            'city'              => $this->sanitize($address->getCity(), 256),
            //    country_code	column	UppercaseEnum	not NULL Country for this address. Must be ISO-3166-2 codes
            'country_code'      => $address->getCountry(),
            //    extra	column	JSON	not NULL	{} A JSON object.
            //    first_name	column	String 256 characters or less.
            'first_name'        => $this->sanitize($address->getFirstname(),
                256),
            //    last_name	column	String 256 characters or less.
            'last_name'         => $this->sanitize($address->getLastname(),
                256),
            //    organization_name	column	String 256 characters or less.
            'organization_name' => $this->sanitize($address->getCompany(), 256),
            //    postcode	column	String 256 characters or less.
            'postcode'          => $this->sanitize($address->getPostcode(),
                256),
            //    prefix	column	String 256 characters or less.
            'prefix'            => $this->sanitize($address->getPrefix(), 256),
            //    region_code	column	String 256 characters or less.
            'region_code'       => $this->sanitize($address->getRegionCode(),
                256),
            //    State/province/region for this address.
            //    street_line_1	column	String	not NULL 256 characters or less.
            'street_line_1'     => $this->sanitize($address->getStreet1(), 256),
            //    street_line_2	column	String 256 characters or less.
            'street_line_2'     => $this->sanitize($address->getStreet2(), 256),
            //    street_line_3	column	String 256 characters or less.
            'street_line_3'     => $this->sanitize($address->getStreet3()
                . $address->getStreet4(), 256),
            //    suffix	column	String 256 characters or less.
            'suffix'            => $this->sanitize($address->getSuffix(), 256),
            //    updated	column	DateTime	not NULL ISO format datetime with timezone offset: 1997-07-16T19:20:30.45+01:00. The time when this row was last updated. Read-only.
            //    version	column	Integer	not NULL	1 Current version number of this entry, incremented each time it is changed. Read-only.
            //    audit_user_id	column	Integer ForeignKey('company_user.id') ON DELETE None ID of the user responsible for the last change of this object
            //    order_id	column	Integer	not NULL ForeignKey('order.id') ON DELETE CASCADE
            //    order	relationship	Single Order entry.
            //    created	hybrid_property The time when this row was created. Determined by looking in the history for this table. Read-only.
        );

    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     *
     * @todo sanitize all values
     */
    public function getShippingAddress(Mage_Sales_Model_Order $order)
    {
        $address = $order->getShippingAddress();
        return array(
            //    id	column	Integer	not NULL Primary key.
            //    city	column	String 256 characters or less.
            'city'              => $this->sanitize($address->getCity(), 256),
            //    country_code	column	UppercaseEnum	not NULL Country for this address. Must be ISO-3166-2 codes
            'country_code'      => $address->getCountry(),
            //    extra	column	JSON	not NULL	{} A JSON object.
            //    first_name	column	String 256 characters or less.
            'first_name'        => $this->sanitize($address->getFirstname(),
                256),
            //    last_name	column	String 256 characters or less.
            'last_name'         => $this->sanitize($address->getLastname(),
                256),
            //    organization_name	column	String 256 characters or less.
            'organization_name' => $this->sanitize($address->getCompany(), 256),
            //    postcode	column	String 256 characters or less.
            'postcode'          => $this->sanitize($address->getPostcode(),
                256),
            //    prefix	column	String 256 characters or less.
            'prefix'            => $this->sanitize($address->getPrefix(), 256),
            //    region_code	column	String 256 characters or less.
            'region_code'       => $this->sanitize($address->getRegionCode(),
                256),
            //    State/province/region for this address.
            //    street_line_1	column	String	not NULL 256 characters or less.
            'street_line_1'     => $this->sanitize($address->getStreet1(), 256),
            //    street_line_2	column	String 256 characters or less.
            'street_line_2'     => $this->sanitize($address->getStreet2(), 256),
            //    street_line_3	column	String 256 characters or less.
            'street_line_3'     => $this->sanitize($address->getStreet3()
                . $address->getStreet4(), 256),
            //    suffix	column	String 256 characters or less.
            'suffix'            => $this->sanitize($address->getSuffix(), 256),
            //    updated	column	DateTime	not NULL ISO format datetime with timezone offset: 1997-07-16T19:20:30.45+01:00. The time when this row was last updated. Read-only.
            //    version	column	Integer	not NULL	1 Current version number of this entry, incremented each time it is changed. Read-only.
            //    audit_user_id	column	Integer ForeignKey('company_user.id') ON DELETE None ID of the user responsible for the last change of this object
            //    order_id	column	Integer	not NULL ForeignKey('order.id') ON DELETE CASCADE
            //    order	relationship	Single Order entry.
            //    created	hybrid_property The time when this row was created. Determined by looking in the history for this table. Read-only.
        );
    }

    /**
     * @param string $string
     * @param int    $length
     *
     * @return string
     */
    private function sanitize($string, $length)
    {
        return (string)substr($string, 0, $length);
    }

    /**
     * @param float $price
     *
     * @return string
     */
    private function sanitizePrice($price)
    {
        return sprintf('%.2f', $price);
    }

    private function sanitizeInt($int)
    {
        return sprintf('%d', $int);
    }
}
