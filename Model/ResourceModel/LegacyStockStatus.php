<?php
/**
 * Copyright © TAL All rights reserved.
 */
declare(strict_types=1);

namespace TAL\StockIndex\Model\ResourceModel;

/**
 * Set data to legacy cataloginventory_stock_status table via plain MySql query.
 */
class LegacyStockStatus extends \Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus
{
    public function execute(string $sku, float $quantity, int $status): void
    {
    }
}