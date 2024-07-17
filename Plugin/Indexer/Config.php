<?php
/**
 * Copyright © TAL All rights reserved.
 */

namespace TAL\StockIndex\Plugin\Indexer;

class Config
{
    const MY_INDEXER_ID = "tal_stockindex_indexer";
    /**
     * Get indexers list
     *
     * @return array[]
     */
    public function afterGetIndexers(\Magento\Indexer\Model\Config $subject, $result)
    {
        $indexers = $result;
        foreach ($indexers as $key => $indexer) {
            if($key == self::MY_INDEXER_ID){
                $temp = array($key => $indexers[$key]);
                unset($indexers[$key]);
                $indexers = $temp + $indexers;
            }
        }
        return $indexers;
    }
}