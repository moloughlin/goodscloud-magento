<?php

class GoodsCloud_Sync_Model_Source_Letter_Format
{
    protected $_options;

    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = array(
                array(
                    'value' => 'plain',
                    'label' => Mage::helper('goodscloud_sync')->__('plain'),
                ),
                array(
                    'value' => 'letterhead',
                    'label' => Mage::helper('goodscloud_sync')->__('letterhead'),
                ),
            );
        }

        return $this->_options;
    }
}
