<?php

namespace Config;

/**
 * # --------------------------------------------------------------------
 * * Datatables Configuration
 * # --------------------------------------------------------------------
 *
 * configuration for datatables 
 *
 */
class Datatables
{
    /**
     * # --------------------------------------------------------------------
     * * Table Setting
     * # --------------------------------------------------------------------
     *
     * Example 
     * 'users' => [ 
            'col_title' => 'Nama Depan,Nama Belakang,Alamat,Status',
            'col_data' => 'nama_depan,nama_belakang,alamat,status',
            'helpers' => [
                'status' => ['formatStatus', '{status}'],
            ],
            'numbering' => true,
            'action_button' => 'edit,delete',
        ],
     * 
     * master -> segment / data source
     * header -> Table header (separate with ,) 
     * value -> array key of source data (separate with ,) 
     * number -> true if you want to show consecutive numbers
     * action -> action button
     *
     * @var array
     */
    public $tableSetting = [
        'units' => [
            'col_title' => 'Nama',
            'col_data' => 'name',
            'numbering' => true,
            'action_button' => 'edit,delete',
        ],
        'product_category' => [
            'col_title' => 'Nama',
            'col_data' => 'name',
            'numbering' => true,
            'action_button' => 'edit,delete',
        ],
        'product' => [
            'col_title' => 'Kode,Barcode,Nama,Kategori,Satuan,Harga Beli,Harga Jual, Keterangan',
            'col_data' => 'code,barcode,name,category_name,unit_name,cogs,selling_price,description',
            'helpers' => [
                'cogs' => ['formatIDRHidden', '{cogs}'],
                'selling_price' => ['formatIDR', '{selling_price}'],
            ],
            'numbering' => true,
            'action_button' => 'edit,delete',
        ],
        'user' => [
            'col_title' => 'Nama,Username,Hak Akses,Status',
            'col_data' => 'name,username,group_name,active',
            'helpers' => [
                'active' => ['formatStatus', '{active}'],
            ],
            'numbering' => true,
            'action_button' => 'edit,delete',
        ],
        'customer' => [
            'col_title' => 'Nama,Alamat,Telepon',
            'col_data' => 'name,address,phone',
            'numbering' => true,
            'action_button' => 'edit,delete',
        ],
        'supplier' => [
            'col_title' => 'Nama,Alamat,Telepon',
            'col_data' => 'name,address,phone',
            'numbering' => true,
            'action_button' => 'edit,delete',
        ],
        'stock' => [
            'col_title' => 'Kode,Nama,Kategori,Stok',
            'col_data' => 'code,name,category_name,stock',
            'helpers' => [
                'stock' => ['formatStock', '{stock}|{unit_name}'],
            ],
            'numbering' => true,
            'action_button' => 'detailStock',
        ],
        'stock_out' => [
            'col_title' => 'Tanggal,Kode,Keterangan,Total',
            'col_data' => 'date,invoice_no,note,grand_total',
            'helpers' => [
                'date' => ['formatDateSimple', '{date}'],
                'grand_total' => ['formatIDR', '{grand_total}'],
            ],
            'numbering' => true,
            'action_button' => 'detailStockOut',
        ],
        'sales' => [
            'col_title' => 'Tanggal,Invoice,Total',
            'col_data' => 'date,invoice_no,grand_total',
            'helpers' => [
                'date' => ['formatDateSimple', '{date}'],
                'grand_total' => ['formatIDR', '{grand_total}'],
            ],
            'numbering' => true,
            'action_button' => 'detailSales',
        ],
        'stock_purchase' => [
            'col_title' => 'Nomor Invoice,Tanggal Pembelian,Supplier',
            'col_data' => 'invoice_no,date,supplier',
            'helpers' => [
                'date' => ['formatDateSimple', '{date}'],
            ],
            'numbering' => true,
            'action_button' => 'detailStockPurchase',
        ],
        'finance_income' => [
            'col_title' => 'Akun,Tanggal Pemasukan,Total,Keterangan',
            'col_data' => 'acc_name,created_at,amount,description',
            'helpers' => [
                'created_at' => ['formatDateSimple', '{created_at}'],
                'amount' => ['formatIDR', '{amount}'],
            ],
            'numbering' => true,
            'action_button' => 'detailIncome',
        ],
        'finance_expense' => [
            'col_title' => 'Akun,Tanggal Pengeluaran,Total,Keterangan',
            'col_data' => 'acc_name,created_at,amount,description',
            'helpers' => [
                'created_at' => ['formatDateSimple', '{created_at}'],
                'amount' => ['formatIDR', '{amount}'],
            ],
            'numbering' => true,
            'action_button' => 'detailExpense',
        ],
        'debt' => [
            'col_title' => 'Nomor Invoice,Supplier,Total,Tanggal Jatuh Tempo,Status',
            'col_data' => 'invoice_no,supplier,total,due_date,status',
            'helpers' => [
                'due_date' => ['formatDateSimple', '{due_date}'],
                'status' => ['formatStatusDC', '{status}'],
            ],
            'numbering' => true,
        ],
        'credit' => [
            'col_title' => 'Transaksi,Keterangan,Total,Tanggal Jatuh Tempo,Status',
            'col_data' => 'invoice_no,description,amount,due_date,status',
            'helpers' => [
                'due_date' => ['formatDateSimple', '{due_date}'],
                'amount' => ['formatIDR', '{amount}'],
                'status' => ['formatStatusDC', '{status}'],
            ],
            'numbering' => true,
            'action_button' => 'detailCredit,payCredit',
        ],
    ];

    public $loadHelpers = ['formatter'];

    /**
     * # --------------------------------------------------------------------
     * * Souce Data Model
     * # --------------------------------------------------------------------
     *
     * Example
     * 'users' => 'UsersModel',
     *
     * @var array
     */
    public $sourceData = [
        'units' => 'App\Models\UnitsModel',
        'product_category' => 'App\Models\ProductCategoriesModel',
        'product' => 'App\Models\ProductsModel',
        'user' => 'App\Models\UserExtensionModel',
        'customer' => 'App\Models\CustomerModel',
        'supplier' => 'App\Models\SupplierModel',
        'stock' => 'App\Models\ProductsModel',
        'stock_purchase' => 'App\Models\InvoiceStockPurchaseModel',
        'stock_out' => 'App\Models\InvoiceStockSalesMoveModel',
        'sales' => 'App\Models\InvoiceStockSalesModel',
        'finance_income' => 'App\Models\FinancialIncomeModel',
        'finance_expense' => 'App\Models\FinancialExpenseModel',
        'debt' => 'App\Models\AccountPayableModel',
        'credit' => 'App\Models\AccountReceivableModel',
    ];

    /**
     * # --------------------------------------------------------------------
     * * Button Action
     * # --------------------------------------------------------------------
     *
     * @var array
     */
    public $buttonAction = [
        'detail' => 'buttons/detail',
        'edit' => 'buttons/edit',
        'delete' => 'buttons/delete',
        'detailSales' => 'sales/button_detail',
        'detailStock' => 'inventory/stock/button_detail',
        'detailStockPurchase' => 'inventory/stock_purchase/button_detail',
        'detailStockOut' => 'inventory/stock_out/button_detail',
        'detailIncome' => 'finance/income/button_detail',
        'detailExpense' => 'finance/expense/button_detail',
        'detailCredit' => 'debtcredit/credit/button_detail',
        'payCredit' => 'debtcredit/credit/button_pay',
    ];
}
