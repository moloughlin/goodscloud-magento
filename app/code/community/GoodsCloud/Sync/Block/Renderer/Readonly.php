<?php

class GoodsCloud_Sync_Block_Renderer_Readonly
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->setReadonly(true);

        return parent::render($element);
    }

}
