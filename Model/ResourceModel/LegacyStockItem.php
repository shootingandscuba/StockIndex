<?php
/**
 * Copyright © TAL All rights reserved.
 */
declare(strict_types=1);

namespace TAL\StockIndex\Model\ResourceModel;

/**
 * Set data to legacy cataloginventory_stock_item table via plain MySql query
 */
class LegacyStockItem extends \Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockItem
{
    public function execute(string $sku, float $quantity, int $status)
    {
    }
}