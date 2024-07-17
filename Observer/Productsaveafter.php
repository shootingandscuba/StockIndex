<?php
/**
 * Copyright © TAL All rights reserved.
 */

namespace TAL\StockIndex\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Response\RedirectInterface;
use TAL\StockIndex\Helper\Data;
use TAL\StockIndex\Controller\Stock;

class Productsaveafter implements ObserverInterface 
{
    protected $_request;
    protected $_resource;
    protected $_redirectInterface;
    protected $_resultFactory;
    protected $_scopeConfig;
    protected $_helper;
    protected $_stock;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\App\Response\RedirectInterface $redirectInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \TAL\StockIndex\Helper\Data $helper,
        \TAL\StockIndex\Controller\Stock $stock,
    )
    {
        $this->_request = $request;
        $this->_resource = $resource;
        $this->_redirectInterface = $redirectInterface;
        $this->_scopeConfig = $scopeConfig;
        $this->_helper = $helper;
        $this->_stock = $stock;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {
        if( $this->_helper->getConfig('tal/stockindex/product_save_action') > 0 ) {
            $post_data = $this->_request->getPost()->toArray();
            $product_id = $post_data['product']['stock_data']['product_id'];
            $sources = $post_data['sources']['assigned_sources'];
            $sources['backorders'] = $post_data['product']['stock_data']['backorders'];
            $sources['sku'] = $post_data['product']['sku'];
            $this->_stock->onProductSave( $product_id, $sources );
        }
    }
}