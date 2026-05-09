<?php

/**
 * ERP Configuration
 *
 * Semantic account IDs: update these after seeding the chart of accounts.
 * These IDs map to specific accounts in the `accounts` table.
 */
return [
    'default_warehouse_id' => env('ERP_DEFAULT_WAREHOUSE_ID', 1),

    'accounts' => [
        'cash'        => env('ERP_ACCOUNT_CASH', 1),
        'ar'          => env('ERP_ACCOUNT_AR', 2),          // Accounts Receivable
        'inventory'   => env('ERP_ACCOUNT_INVENTORY', 3),
        'ap'          => env('ERP_ACCOUNT_AP', 4),          // Accounts Payable
        'revenue'     => env('ERP_ACCOUNT_REVENUE', 5),
        'cogs'        => env('ERP_ACCOUNT_COGS', 6),        // Cost of Goods Sold
        'tax_payable' => env('ERP_ACCOUNT_TAX', 7),
        'card'        => env('ERP_ACCOUNT_CARD', 8),        // Card/POS receipts
    ],
];
