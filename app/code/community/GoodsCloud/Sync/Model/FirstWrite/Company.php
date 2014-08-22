<?php

class GoodsCloud_Sync_Model_FirstWrite_Company extends GoodsCloud_Sync_Model_FirstWrite_Base
{
    /**
     * @return GoodsCloud_Sync_Model_Api_Company
     */
    public function getCompany()
    {
        return $this->getApi()->getCompany();
    }
}
