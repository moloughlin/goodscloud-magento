<?php

class GoodsCloud_Sync_Block_Renderer_Button
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /*
    * Set template
    */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('goodscloud_sync/system/config/button.phtml');
    }

    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(
        Varien_Data_Form_Element_Abstract $element
    ) {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxCheckUrl()
    {
        return Mage::helper('adminhtml')
            ->getUrl('adminhtml/adminhtml_atwixtweaks/check');
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        /* @var $button Mage_Adminhtml_Block_Widget_Button */
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'id'      => 'goodscloud_sync_button',
                'label'   => $this->helper('goodscloud_sync')
                    ->__('Get AWS Settings'),
                'onclick' => 'javascript:check(); return false;'
            ));

        return $button->toHtml();
    }
}
