<?php

class GoodsCloud_Sync_Helper_Api extends Mage_Core_Helper_Abstract
{
    /**
     * get the baseuri for api requests
     *
     * @return string
     */
    public function getUri()
    {
        return Mage::getStoreConfig('goodscloud/advanced/base_url');
    }

    /**
     * email address to log into goodscloud api
     *
     * @return string
     */
    public function getEmail()
    {
        return Mage::getStoreConfig('goodscloud/basic/email');
    }

    /**
     * password to log into goodscloud api
     *
     * @return string
     */
    public function getPassword()
    {
        return Mage::getStoreConfig('goodscloud/basic/password');
    }

    public function getIgnoredAttributes()
    {
        return array_keys(Mage::getStoreConfig('goodscloud_sync/api/ignored_attributes'));
    }
}