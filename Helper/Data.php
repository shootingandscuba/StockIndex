<?php
/**
 * Copyright © TAL All rights reserved.
 */

namespace TAL\StockIndex\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function getConfig($config_path , $store = null) {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}