ALTER TABLE
    `sollu-pos`.`financials`
MODIFY
    COLUMN `amount` decimal(20, 2) NOT NULL;

-- --------------------------------------------------------
ALTER TABLE
    `sollu-pos`.`products`
MODIFY
    COLUMN `cogs` decimal(20, 2) NOT NULL;

ALTER TABLE
    `sollu-pos`.`products`
MODIFY
    COLUMN `selling_price` decimal(20, 2) NOT NULL;

-- --------------------------------------------------------
ALTER TABLE
    `sollu-pos`.`stocks`
MODIFY
    COLUMN `cogs` decimal(20, 2) NOT NULL;

ALTER TABLE
    `sollu-pos`.`stocks`
MODIFY
    COLUMN `selling_price` decimal(20, 2) NOT NULL;

-- --------------------------------------------------------
ALTER TABLE
    `sollu-pos`.`stock_purchases`
MODIFY
    COLUMN `cogs` decimal(20, 2) NOT NULL;

ALTER TABLE
    `sollu-pos`.`stock_purchases`
MODIFY
    COLUMN `selling_price` decimal(20, 2) NOT NULL;

-- --------------------------------------------------------
ALTER TABLE
    `sollu-pos`.`invoice_stock_purchases`
MODIFY
    COLUMN `total` decimal(20, 2) NOT NULL;

ALTER TABLE
    `sollu-pos`.`invoice_stock_purchases`
MODIFY
    COLUMN `discount` decimal(20, 2) NOT NULL;

ALTER TABLE
    `sollu-pos`.`invoice_stock_purchases`
MODIFY
    COLUMN `grand_total` decimal(20, 2) NOT NULL;

-- --------------------------------------------------------
ALTER TABLE
    `sollu-pos`.`stock_sales`
MODIFY
    COLUMN `price` decimal(20, 2) NOT NULL;

ALTER TABLE
    `sollu-pos`.`stock_sales`
MODIFY
    COLUMN `discount` decimal(20, 2) NOT NULL;

ALTER TABLE
    `sollu-pos`.`stock_sales`
MODIFY
    COLUMN `sub_total` decimal(20, 2) NOT NULL;

-- --------------------------------------------------------
ALTER TABLE
    `sollu-pos`.`invoice_stock_sales`
MODIFY
    COLUMN `total` decimal(20, 2) NOT NULL;

ALTER TABLE
    `sollu-pos`.`invoice_stock_sales`
MODIFY
    COLUMN `discount` decimal(20, 2) NOT NULL;

ALTER TABLE
    `sollu-pos`.`invoice_stock_sales`
MODIFY
    COLUMN `grand_total` decimal(20, 2) NOT NULL;

ALTER TABLE
    `sollu-pos`.`invoice_stock_sales`
MODIFY
    COLUMN `pay` decimal(20, 2) NOT NULL;

ALTER TABLE
    `sollu-pos`.`invoice_stock_sales`
MODIFY
    COLUMN `return` decimal(20, 2) NOT NULL;

-- --------------------------------------------------------
ALTER TABLE
    `sollu-pos`.`financials`
MODIFY
    COLUMN `amount` decimal(20, 2) NOT NULL;

-- --------------------------------------------------------
ALTER TABLE
    `sollu-pos`.`account_payable`
MODIFY
    COLUMN `amount` decimal(20, 2) NOT NULL;

-- --------------------------------------------------------
ALTER TABLE
    `sollu-pos`.`account_receivable`
MODIFY
    COLUMN `amount` decimal(20, 2) NOT NULL;

ALTER TABLE
    `sollu-pos`.`account_receivable`
MODIFY
    COLUMN `pay_amount` decimal(20, 2) NOT NULL;

-- --------------------------------------------------------
ALTER TABLE
    `sollu-pos`.`account_payable_details`
MODIFY
    COLUMN `amount` decimal(20, 2) NOT NULL;

-- --------------------------------------------------------
ALTER TABLE
    `sollu-pos`.`account_receivable_details`
MODIFY
    COLUMN `amount` decimal(20, 2) NOT NULL;

-- --------------------------------------------------------
ALTER TABLE
    `sollu-pos`.`stock_out`
MODIFY
    COLUMN `cogs` decimal(20, 2) NOT NULL;

-- --------------------------------------------------------
ALTER TABLE
    `sollu-pos`.`cashier_logs`
MODIFY
    COLUMN `begining_balance` decimal(20, 2) NOT NULL;

ALTER TABLE
    `sollu-pos`.`cashier_logs`
MODIFY
    COLUMN `total_sales` decimal(20, 2) NOT NULL;

ALTER TABLE
    `sollu-pos`.`cashier_logs`
MODIFY
    COLUMN `ending_balance` decimal(20, 2) NOT NULL;

-- --------------------------------------------------------
ALTER TABLE
    `sollu-pos`.`stock_sales`
MODIFY
    COLUMN `cogs` decimal(20, 2) NOT NULL;