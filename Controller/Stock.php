<?php
/**
 * Copyright © TAL All rights reserved.
 */

namespace TAL\StockIndex\Controller;

class Stock {

    protected $_resource;
    protected $_connection;
    protected $_logger;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Psr\Log\LoggerInterface $logger,
    ) {
        $this->_resource = $resource;
        $this->_connection = $this->_resource->getConnection('core_write');
        $this->_logger = $logger;
    }

    /* Converts a string or array of product id values into a CSV string for use with query */
    private static function productIds( $ids = null, $delimiter = "" ) {
        if( empty( $ids ) ) {
            return false;
        }
        if( !is_array( $ids ) ) {
            $ids = array(
                '0' => $ids,
            );
        }
        if( !empty( $ids ) ) {
            $string = "";
            $first = array_key_first($ids);
            foreach( $ids as $key =>$value ) {
                $string .= ( $key == $first ? "" : ", " ) . $delimiter . $value . $delimiter;
            }
            return $string;
        }
        return $ids;
    }

    public function stockBySourceItemIds( $ids ) {
        try {
            if( empty( $ids ) ) {
                return false;
            }
            if( !is_array( $ids ) ) {
                $ids = array(
                    '0' => $ids,
                );
            }
            $inventory_source_item = $this->_resource->getTableName('inventory_source_item');
            $catalog_product_entity = $this->_resource->getTableName('catalog_product_entity');
            $product_ids = [];
            $skus = [];


            /* Find a SKU for the Source Item Id */
            foreach( $ids as $key => $source_item_id ) {
                $bind = ['source_item_id' => $source_item_id];
                $query = $this->_connection
                ->select()
                ->from(
                    ['_source_item' => $inventory_source_item],
                    ['product_sku' => '_source_item.sku']
                )
                ->where('_source_item.source_item_id = :source_item_id')
                ->limit('1');
                $results = $this->_connection->fetchAll($query, $bind);
                if( !empty( $results ) ) {
                    $pointer = array_key_first($results);
                    $skus[] = $results[ $pointer ]['product_sku']; 
                }
            }

            $skus = array_unique( $skus );

            /* Find a Product ID for the SKU */
            foreach( $skus as $key => $product_sku ) {
                $bind = ['product_sku' => $product_sku];
                $query = $this->_connection
                ->select()
                ->from(
                    ['_product_entity' => $catalog_product_entity],
                    ['product_id' => '_product_entity.entity_id']
                )
                ->where('_product_entity.sku = :product_sku')
                ->limit('1');
                $results = $this->_connection->fetchAll($query, $bind);
                if( !empty( $results ) ) {
                    $pointer = array_key_first($results);
                    $product_ids[] = (int) $results[ $pointer ]['product_id']; 
                }
            }

            $this->allStock( $product_ids );
        }
        catch(Exception $e) {    
            $this->_logger->debug("Exception: " . $e->getMessage() );
        }
    }

    public function stockByIds( $ids = null ){
        try {
            $this->allStock( $ids );
        }
        catch(Exception $e) {    
            $this->_logger->debug("Exception: " . $e->getMessage() );
        }
    }

    public function execute(){
        try {
            $this->allStock();
        }
        catch(Exception $e) {    
            $this->_logger->debug("Exception: " . $e->getMessage() );
        }
    }

    public function allStock( $ids = null ){
        try {

            if( !empty( $ids ) ) {
                $ids = self::productIds( $ids );
            }

            $inventory_source_item = $this->_resource->getTableName('inventory_source_item');
            $catalog_product_entity = $this->_resource->getTableName('catalog_product_entity');
            $cataloginventory_stock_item = $this->_resource->getTableName('cataloginventory_stock_item');

            if( !empty( $ids ) ) {
                $bind = ['product_type' => 'simple', 'product_ids' => $ids ];
            }
            else {
                $bind = ['product_type' => 'simple'];
            }
            $query = $this->_connection
            ->select()
            ->from(
                ['_source_item' => $inventory_source_item],
                ['is_in_stock' => 'sum(_source_item.status)','qty' => 'sum(_source_item.quantity)']
            )
            ->joinLeft(
                ['_product_entity' => $catalog_product_entity],
                '_source_item.sku =_product_entity.sku',
                ['product_id' => '_product_entity.entity_id']
            )
            ->joinLeft(
                ['_stock_item' => $cataloginventory_stock_item],
                '_product_entity.entity_id =_stock_item.product_id',
                ['_stock_item.backorders']
            );
            if( !empty( $ids ) ) {
                $query->where('_product_entity.entity_id IS NOT NULL AND _product_entity.entity_id IN (:product_ids) AND _product_entity.type_id = :product_type');
            }
            else {
                $query->where('_product_entity.entity_id IS NOT NULL AND _product_entity.type_id = :product_type');
            }
            $query->group('_source_item.sku');

            $results = $this->_connection->fetchAll($query, $bind);

            foreach( $results as $key => $stock_item ) {
                if( $stock_item['backorders'] > 0 ) {
                    $results[ $key ]['backorders'] = 2;
                }
                if( ( $stock_item['backorders'] > 0 ) || ( $stock_item['is_in_stock'] > 0 ) ) {
                    $results[ $key ]['is_in_stock'] = 1;
                }
                $results[ $key ]['stock_id'] = 1;
                $results[ $key ]['backorders'] = intval($results[ $key ]['backorders']);
                $results[ $key ]['is_in_stock'] = intval($results[ $key ]['is_in_stock']);
                $results[ $key ]['product_id'] = intval($results[ $key ]['product_id']);
            }

            $this->_connection->insertOnDuplicate(
                $cataloginventory_stock_item,
                $results,
                ['is_in_stock','qty', 'product_id', 'backorders', 'stock_id']
            );
        }
        catch(Exception $e) {
            $this->_connection->rollBack();
            $this->_logger->debug("Exception: " . $e->getMessage() );
        }
    }    

    public function onProductSave( $product_id = null, $sources = null ){
        try { 

            /* Data Tables */
            $cataloginventory_stock_item = $this->_resource->getTableName('cataloginventory_stock_item');
            $inventory_source_item = $this->_resource->getTableName('inventory_source_item');

            /* Compact Multiple Stock Sources into a Stock item */
            $quantity = 0;
            $status = 0;
            $backorders = 0 + ( isset( $sources['backorders'] ) ? $sources['backorders'] : 0 );
            if( !empty( $sources ) && is_array( $sources ) ) {
                foreach( $sources as $key => $stock ) {
                    if( !in_array( $key, array( 'backorders', 'sku' ) ) ) {
                        $quantity += $stock['quantity'];
                        $status += $stock['status'];

                        $this->_connection->update(
                            $inventory_source_item,
                            $insertData = [
                                "quantity" => $stock['quantity'],
                                "status" => $stock['status'],
                            ],
                            ["source_code = ?" =>  $stock['source_code'], "sku =?" => $sources['sku'], ]
                        );
                    }
                }
            }

            /* Fetch Existing Data for Stockitem */
            
            $bind = ['product_id' => (int)$product_id];
            $query = $this->_connection
            ->select()
            ->from(
                ['_stock_item' => $cataloginventory_stock_item],
                ['is_in_stock','backorders']
            )->where('product_id = :product_id');
            $stock_item = $this->_connection->fetchRow($query, $bind);

            /* Recalculate Simple Stock Status */
            switch ( true ) {
                case ( $backorders > 0 ):
                    $cataloginventory_stock_status = 1;
                    $cataloginventory_backorders = 2;
                    break;
                case ( ( $status > 0 ) && ( $quantity > 0 ) ):
                    $cataloginventory_stock_status = 1;
                    $cataloginventory_backorders = $stock_item['backorders'];
                    break;
                case ( ( $status == 0 ) && ( $quantity == 0 ) ):
                    $cataloginventory_stock_status = 0;
                    $cataloginventory_backorders = $stock_item['backorders'];
                    break;
                default:
                    $cataloginventory_stock_status = $stock_item['is_in_stock'];
                    $cataloginventory_backorders = $stock_item['backorders'];
                    break;
            }

            /* Update database stock data */
            $insertData = array(
                "product_id" => $product_id,
                "qty" => $quantity,
                "is_in_stock" => $cataloginventory_stock_status,
                "backorders" => $cataloginventory_backorders,
            );

            $this->_connection->update(
                $cataloginventory_stock_item,
                $insertData,
                ['product_id = ?' => (int) $product_id ]
            );

        }
        catch(Exception $e) {
            $this->_connection->rollBack();
            $this->_logger->debug("Exception: " . $e->getMessage() );
        }
    }

}
