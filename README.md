# StockIndex
Magento 2.4.6 Plugin that alters the MSI Stock Logic

Natively, the default_stock source quantity and stock status is updated from inventory_source_item into the cataloginventory_stock_item table when indexing. Stores that have added other sources like ourselves often find that their stock status and quantity is incorrect due to the native indexing process.

This module adds an additional action after saving a product, which updates the cataloginventory_stock_item table in real time, and disables the \Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockItem and \Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus methods. These two methods are the ones that write the default_stock values into the table.

In addition, a new indexer is added, which is then processed first in the inventory index group, so that these values are corrected and passed to the cataloginventory_stock_item table. This indexer supports being run from the CLI as well as adding a cronjob that tracks manual database changes
