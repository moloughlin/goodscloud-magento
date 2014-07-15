<?php

require 'GoodsCloud/goodscloud.class.php';

class GoodsCloud_Sync_Model_Api_Factory
{
    /**
     * username or password wrong
     */
    const ERROR_CODE_CREDENTIALS_INCORRECT = 1;

    /**
     * initialises the api with username and password and handles errors
     *
     * @return Goodscloud
     *
     * @throws Exception
     * @throws GoodsCloud_Sync_Model_Api_Exception_WrongCredentials
     */
    public function getApi()
    {
        try {
            /* @var $helper GoodsCloud_Sync_Helper_Api */
            $helper = Mage::helper('goodscloud_sync/api');
            $uri = $helper->getUri();
            $email = $helper->getEmail();
            $password = $helper->getPassword();
            $api = $this->createApi($uri, $email, $password);
            return $api;
        } catch (Exception $e) {
            if ($e->getCode() == self::ERROR_CODE_CREDENTIALS_INCORRECT) {
                throw new GoodsCloud_Sync_Model_Api_Exception_WrongCredentials($e->getMessage());
            }
        }
        throw new Exception('Api was not created but the credentials were correct - something went totally wrong.');
    }

    /**
     * @param string $uri
     * @param string $email
     * @param string $password
     *
     * @return Goodscloud
     */
    private function createApi($uri, $email, $password)
    {
        return new Goodscloud($uri, $email, $password);
    }
}