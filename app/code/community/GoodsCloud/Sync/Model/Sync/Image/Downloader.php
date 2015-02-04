<?php

class GoodsCloud_Sync_Model_Sync_Image_Downloader
{
    const BUCKET_SANDBOX = 'goodscloud-image-sandbox';
    const BUCKET_PRODUCTION = 'goodscloud-image';

    /**
     * @var string
     */
    private $awsAccessKey;

    /**
     * @var string
     */
    private $secret;

    /**
     * @var int
     */
    private $expires;

    public function __construct()
    {
        $this->getAwsAccessKeyId();
    }

    /**
     * @param string $image
     *
     * @return string
     */
    public function getLink($image)
    {
        $link = 'https://' . $this->getBucket() . '.s3.amazonaws.com/vault'
            . $this->getCompanyId() . '/' . $image . '?AWSAccessKeyId='
            . $this->awsAccessKey . '&Expires=' . $this->expires;

        $signature = urlencode(base64_encode(hash_hmac('sha1',
            utf8_encode($link), $this->secret)));

        return $link . '&Signature=' . $signature;
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
        $api = Mage::getModel('goodscloud_sync/api_factory')->getApi();
        $session = $api->get_session();
        $this->awsAccessKey = $session->auth->access;
        $this->secret = $session->auth->secret;
        $this->expires = strtotime($session->auth->expires);
    }
}
