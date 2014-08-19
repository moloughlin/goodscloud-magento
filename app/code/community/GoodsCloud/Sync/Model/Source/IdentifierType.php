<?php

class GoodsCloud_Sync_Model_Source_IdentifierType
{
    protected $_options;

    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = array(
                array(
                    'value' => 'gtin',
                    'label' => 'GTIN'
                ),
                array(
                    'value' => 'ean',
                    'label' => 'EAN'
                ),
                array(
                    'value' => 'upc',
                    'label' => 'UPC'
                )
            );
        }
        return $this->_options;
    }
}
