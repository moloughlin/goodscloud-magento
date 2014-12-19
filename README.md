# GoodsCloud Sync

This magento extension integrates your magento store into the GoodsCloud universe.

## Install

### modman
Just use modman to clone this repo. Install [SCP](https://github.com/obigroup/magento-configurable-simple) and [AvS_FastSimpleImport](https://github.com/avstudnitz/AvS_FastSimpleImport)

### composer.json

Add the repositories  and goodscloud_sync to your composer.json:

    {
      "require": {"goodscloud/magento_sync": "dev-master"},
      "minimum-stability": "dev",
      "repositories": [
        {
          "type": "vcs",
          "url": "https://github.com/goodscloud/goodscloud-magento.git"
        },
        {
          "type": "composer",
          "url": "http://packages.firegento.com"
        },
        {
          "type": "vcs",
          "url": "https://github.com/obigroup/magento-configurable-simple.git"
        }
      ]
    }    
