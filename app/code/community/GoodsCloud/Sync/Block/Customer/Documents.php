<?php

class GoodsCloud_Sync_Block_Customer_Documents
    extends Mage_Sales_Block_Order_Info
{
    const TYPE_INVOICE = 'invcoice';
    const TYPE_CREDITNOTE = 'creditnote';

    const BUCKET_SANDBOX = 'goodscloud-document-sandbox';
    const BUCKET_PRODUCTION = 'goodscloud-document';

    /**
     * @return GoodsCloud_Sync_Model_Api_Order
     */
    public function getOrderFromGc()
    {
        $order = $this->getOrder();
        $api = Mage::getModel('goodscloud_sync/api');

        return $api->getOrderByExternalId($order->getIncrementId());
    }

    public function getInvoiceLink($id)
    {
        $format = Mage::helper('goodscloud_sync/document')->getInvoiceFormat();

        return $this->getLink($id, self::TYPE_INVOICE, $format);
    }

    public function getCreditNoteLink($id)
    {
        $format = Mage::helper('goodscloud_sync/document')
            ->getCreditnoteFormat();

        return $this->getLink($id, self::TYPE_CREDITNOTE, $format);
    }

    private function getLink($id, $type, $format)
    {
        return 'https://' . $this->getBucket() . '.s3.amazonaws.com/vault'
        . $this->getCompanyId() . '/' . $type . '-' . $id . '-' . $format
        . '.pdf' . '?AWSAccessKeyId=' . $this->getAwsAccessKeyId()
        . '&Expires=1378222854&Signature=ZZZ';
    }

    private function getCompanyId()
    {
        return Mage::helper('goodscloud_sync/api')->getCompanyId();
    }

    private function getBucket()
    {
        if (Mage::helper('goodscloud_sync/api')->isSandboxMode()) {
            return self::BUCKET_SANDBOX;
        } else {
            return self::BUCKET_PRODUCTION;
        }
    }

    private function getAwsAccessKeyId()
    {
        if (!$this->awsAccessKey) {
            $api = Mage::getModel('goodscloud_sync/api_factory')->getApi();
            $session = $api->get_session();
            $this->awsAccessKey = $session->auth->access;
        }

        return $this->awsAccessKey;
    }
}
