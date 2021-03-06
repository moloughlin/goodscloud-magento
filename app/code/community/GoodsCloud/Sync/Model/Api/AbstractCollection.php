<?php

class GoodsCloud_Sync_Model_Api_AbstractCollection
    extends Varien_Data_Collection
{
    /**
     * @var int
     */
    private $_lastPageNumber;

    /**
     * @param $lastPageNumber
     */
    public function setLastPageNumber(
        $lastPageNumber
    ) {
        $this->_lastPageNumber = $lastPageNumber;
    }

    /**
     * @return int
     */
    public function getLastPageNumber()
    {
        return (int)$this->_lastPageNumber;
    }
}
