<?php

class GoodsCloud_Sync_Model_Api_Exception_Exception extends Mage_Core_Exception
{
    /**
     * details of the error
     *
     * @var string
     */
    private $details;

    /**
     * more details
     *
     * @var string
     */
    private $longDetails;

    /**
     * @return string
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param string $details
     */
    public function setDetails($details)
    {
        $this->details = $details;
    }

    /**
     * @return string
     */
    public function getLongDetails()
    {
        return $this->longDetails;
    }

    /**
     * @param string $long_details
     */
    public function setLongDetails($long_details)
    {
        $this->longDetails = $long_details;
    }
}