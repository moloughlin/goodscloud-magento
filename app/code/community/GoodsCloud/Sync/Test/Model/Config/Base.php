<?php

class GoodsCloud_Sync_Test_Model_Config_Base extends EcomDev_PHPUnit_Test_Case_Config
{
    public function testModel()
    {
        $this->assertModelAlias('goodscloud_sync/api', 'GoodsCloud_Sync_Model_Api');
    }

    public function testHelper()
    {
        $this->assertHelperAlias('goodscloud_sync', 'GoodsCloud_Sync_Helper_Data');
    }

    public function testConfig()
    {
        $this->assertModuleVersionGreaterThanOrEquals($this->expected('module')->getVersion());
        $this->assertModuleCodePool($this->expected('module')->getCodePool());
        $this->assertSchemeSetupScriptVersions(
            '1.0.0', $this->expected('module')->getVersion(), null, 'goodscloud_sync_setup'
        );
        $this->assertSchemeSetupScriptVersions(
            '1.0.0', $this->expected('module')->getVersion(), null, 'goodscloud_sync_eav_setup'
        );
    }
}
