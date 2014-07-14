<?php

require 'GoodsCloud/goodscloud.class.php';

class GoodsCloud_Sync_Model_Api
{
    const ERROR_CODE_CREDENTIALS_INCORRECT = 1;

    /**
     * create goodscloud api object
     */
    function __construct()
    {
        try {
            // TODO put credentials in config
            $uri = 'http://sandbox.goodscloud.com';
            $email = 'mymail@fbtest.de';
            $password = '!2';
            $this->api = new Goodscloud($uri, $email, $password);
        } catch (Exception $e) {
            if ($e->getCode() == self::ERROR_CODE_CREDENTIALS_INCORRECT) {
                throw new GoodsCloud_Sync_Model_Exception_WrongCredentials();
            }
        }
    }
}
