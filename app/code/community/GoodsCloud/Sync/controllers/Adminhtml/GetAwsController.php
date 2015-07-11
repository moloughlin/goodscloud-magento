<?php

class GoodsCloud_Sync_Adminhtml_GetAwsController
    extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $api = Mage::getModel('goodscloud_sync/api_factory')->getApi();
        $this->getResponse()->setBody($api->get_session()->auth->access);
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/goodscloud_sync');
    }
}
