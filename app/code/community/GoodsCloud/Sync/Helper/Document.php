<?php

class GoodsCloud_Sync_Helper_Document extends Mage_Core_Helper_Abstract
{
    const XML_CONFIG_INVOICE_FORMAT = 'goodscloud_sync/invoice/format';
    const XML_CONFIG_CREDITNOTE_FORMAT = 'goodscloud_sync/creditnote/format';

    public function getInvoiceFormat()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_INVOICE_FORMAT);
    }

    public function getCreditnoteFormat()
    {
        return Mage::getStoreConfig(self::XML_CONFIG_CREDITNOTE_FORMAT);
    }
}
