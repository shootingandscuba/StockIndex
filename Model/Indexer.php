<?php
/**
 * Copyright © TAL All rights reserved.
 */

namespace TAL\StockIndex\Model;
 
use Magento\Framework\Indexer\ActionInterface as IndexerInterface;
use Magento\Framework\Mview\ActionInterface as MviewInterface;
use TAL\StockIndex\Controller\Stock;
use TAL\StockIndex\Helper\Data;
 
class Indexer implements IndexerInterface, MviewInterface
{

    protected $stock;
    protected $_logger;
    protected $_helper;

    public function __construct(
        \TAL\StockIndex\Controller\Stock $stock,
        \Psr\Log\LoggerInterface $logger,
        \TAL\StockIndex\Helper\Data $helper,
    )
    {
        $this->stock = $stock;
        $this->_logger = $logger;
        $this->_helper = $helper;
    }

    /**
     * Works in runtime for process indexer in "Update on schedule" Mode.
     */
    public function execute( $source_item_ids = null ) {
        try {
            if( $this->_helper->getConfig('tal/stockindex/product_index_action') > 0 ) {
                $this->stock->stockBySourceItemIds($source_item_ids);
            }
        }
        catch(Exception $e) {    
            $this->_logger->debug("Exception: " . $e->getMessage() );
        }
    }

    /**
     * Works in runtime for reindex via command line
     */
    public function executeFull() {
        try {
            if( $this->_helper->getConfig('tal/stockindex/product_index_action') > 0 ) {
                $this->stock->allStock();
            }
        }
        catch(Exception $e) {    
            $this->_logger->debug("Exception: " . $e->getMessage() );
        }
    }

    /**
     * Works in runtime for several items eg Mass Actions
     */
    public function executeList(array $source_item_ids) {
        try {
            if( $this->_helper->getConfig('tal/stockindex/product_index_action') > 0 ) {
                $this->stock->stockBySourceItemIds($source_item_ids);
            }
        }
        catch(Exception $e) {    
            $this->_logger->debug("Exception: " . $e->getMessage() );
        }
    }

    /**
     * Works in runtime for a single entity using plugins
     */
    public function executeRow($source_item_ids) {
        try {
            if( $this->_helper->getConfig('tal/stockindex/product_index_action') > 0 ) {
                $this->stock->stockBySourceItemIds($source_item_ids);
            }
        }
        catch(Exception $e) {    
            $this->_logger->debug("Exception: " . $e->getMessage() );
        }
    }
}