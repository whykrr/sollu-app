import { computed } from 'vue'
import { useEnum } from '@/Composable/useEnum'
import { faBox, faBoxes, faChartLine, faChartPie, faPercent, faReceipt, faUsers, faUserTie } from '@fortawesome/free-solid-svg-icons'

export const getMainSidebars = (enums = { FeatureEnum: {} }) => [
    {
        type: 'item',
        url: route('overview'),
        icon: faChartPie,
        label: 'Ringkasan',
        permissions: [],
        activeRoute: 'overview',
    },
    {
        type: 'dropdown',
        url: '#',
        icon: faChartLine,
        label: 'Laporan',
        permissions: [],
        activeRoute: 'reports',
        items: [
            {
                type: 'item',
                url: route('reports.sales.index'),
                label: 'Laporan Penjualan',
                feature: enums.FeatureEnum.SALES_REPORTS,
                permissions: ['report.sales.view'],
                activeRoute: 'reports.sales.',
            },
            {
                type: 'item',
                url: route('reports.products.index'),
                label: 'Laporan Produk',
                feature: enums.FeatureEnum.PRODUCT_REPORTS,
                permissions: ['report.product.view'],
                activeRoute: 'reports.products.',
            },
            {
                type: 'item',
                url: route('reports.stocks.index'),
                label: 'Laporan Stock & Aset',
                feature: enums.FeatureEnum.STOCK_REPORTS,
                permissions: ['report.stock.view'],
                activeRoute: 'reports.stocks.',
            },
            {
                type: 'item',
                url: route('reports.cashiers.index'),
                label: 'Laporan Shift & Kas',
                feature: enums.FeatureEnum.CASHIER_REPORTS,
                permissions: ['report.cashier.view'],
                activeRoute: 'reports.cashiers.',
            },
            {
                type: 'item',
                url: route('reports.promotions.index'),
                label: 'Laporan Promo',
                feature: enums.FeatureEnum.PROMO_REPORTS,
                permissions: ['report.promotion.view'],
                activeRoute: 'reports.promotions.',
            },
            {
                type: 'item',
                url: route('reports.customers.index'),
                label: 'Laporan Pelanggan',
                feature: enums.FeatureEnum.CUSTOMER_REPORTS,
                permissions: ['report.customer.view'],
                activeRoute: 'reports.customers.',
            },
        ],
    },
    {
        type: 'dropdown',
        url: '#',
        icon: faReceipt,
        label: 'Transaksi',
        permissions: ['transaction.view'],
        activeRoute: 'transactions',
        items: [
            {
                type: 'item',
                url: route('transactions.sales.index'),
                label: 'Penjualan',
                permissions: ['transaction.view'],
                activeRoute: 'transactions.sales.',
            },
            {
                type: 'item',
                url: route('transactions.shifts.index'),
                label: 'Shift Kasir',
                feature: enums.FeatureEnum.SHIFT_MANAGEMENT,
                permissions: ['transaction.view'],
                activeRoute: 'transactions.shifts.',
            },
        ]
    },
    {
        type: 'dropdown',
        url: '#',
        icon: faBox,
        label: 'Master Produk',
        permissions: [],
        activeRoute: 'master.',
        items: [
            {
                type: 'item',
                url: route('master.categories.index'),
                label: 'Kategori Produk',
                feature: enums.FeatureEnum.PRODUCT_CATEGORIES,
                permissions: [],
                activeRoute: 'master.categories.',
            },
            {
                type: 'item',
                url: route('master.products.index'),
                label: 'Produk',
                feature: enums.FeatureEnum.PRODUCT_CATALOG,
                permissions: [],
                activeRoute: 'master.products.',
            },
            // {
            //     type: 'item',
            //     url: route('master.modifiers.index'),
            //     label: 'Opsi Tambahan',
            //     feature: enums.FeatureEnum.PRODUCT_MODIFIERS,
            //     permissions: [],
            //     activeRoute: 'master.modifiers.',
            // },
        ],
    },
    {
        type: 'dropdown',
        url: '#',
        icon: faBoxes,
        label: 'Inventori',
        feature: enums.FeatureEnum.INVENTORY_MANAGEMENT,
        permissions: [],
        activeRoute: 'inventories.',
        items: [
            // {
            //     type: 'item',
            //     url: route('inventory.raw-materials.index'),
            //     label: 'Bahan Baku',
            // feature: enums.FeatureEnum.RAW_MATERIALS,
            //     permissions: [],
            //     activeRoute: 'inventory.raw-materials.',
            // },
            {
                type: 'item',
                url: route('inventories.stocks.index'),
                label: 'Stok',
                permissions: [],
                activeRoute: 'inventories.stocks.',
            },
            {
                type: 'item',
                url: route('inventory.suppliers.index'),
                label: 'Pemasok / Supplier',
                feature: enums.FeatureEnum.SUPPLIER_MANAGEMENT,
                permissions: [],
                activeRoute: 'inventory.suppliers.',
            },
            {
                type: 'item',
                url: route('inventory.purchases.index'),
                label: 'Pembelian (PO)',
                feature: enums.FeatureEnum.PURCHASE_ORDERS,
                permissions: [],
                activeRoute: 'inventory.purchases.',
            },
            {
                type: 'item',
                url: route('inventory.adjustments.index'),
                label: 'Penyesuaian Stok',
                feature: enums.FeatureEnum.STOCK_ADJUSTMENTS,
                permissions: [],
                activeRoute: 'inventory.adjustments.',
            },
            {
                type: 'item',
                url: route('inventory.opnames.index'),
                label: 'Stok Opname',
                feature: enums.FeatureEnum.STOCK_OPNAME,
                permissions: [],
                activeRoute: 'inventory.opnames.',
            },
            {
                type: 'item',
                url: route('inventory.transfers.index'),
                label: 'Mutasi Stok',
                feature: enums.FeatureEnum.STOCK_TRANSFERS,
                permissions: [],
                activeRoute: 'inventory.transfers.',
            },
        ],
    },
    {
        type: 'item',
        url: route('promotions.index'),
        icon: faPercent,
        label: 'Promosi',
        feature: enums.FeatureEnum.PROMO_MANAGEMENT,
        permissions: [],
        activeRoute: 'promotions',
    },
    {
        type: 'item',
        url: route('customers.index'),
        icon: faUsers,
        label: 'Pelanggan',
        feature: enums.FeatureEnum.CUSTOMER_MANAGEMENT,
        permissions: [],
        activeRoute: 'customers',
    },
    {
        type: 'item',
        url: route('employees.index'),
        icon: faUserTie,
        label: 'Pegawai',
        feature: enums.FeatureEnum.EMPLOYEE_MANAGEMENT,
        permissions: ['user.view'],
        activeRoute: 'employees',
    },
]

export function useMainSidebar() {
    const { enums } = useEnum()
    const mainSidebars = computed(() => getMainSidebars(enums.value))

    return {
        mainSidebars,
        getMainSidebars,
    }
}

export const mainSidebars = getMainSidebars()
