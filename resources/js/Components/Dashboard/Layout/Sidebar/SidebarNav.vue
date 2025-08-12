<template>
    <nav class="flex-1 sidebar">
        <div class="sidebar-container">
            <template v-for="sidebar in sidebars">
                <SidebarItem
                    v-if="!sidebar.items"
                    :to="
                        route().has('dashboard.' + sidebar.route)
                            ? route('dashboard.' + sidebar.route)
                            : '#'
                    "
                    :icon="sidebar.icon"
                    :label="sidebar.label"
                    :active="isActive(sidebar)"
                />
                <SidebarItemExpand
                    v-else
                    to="#"
                    :icon="sidebar.icon"
                    :label="sidebar.label"
                    :active="isActive(sidebar)"
                >
                    <template v-for="item in sidebar.items">
                        <SidebarItem
                            :to="
                                route().has('dashboard.' + item.route)
                                    ? route('dashboard.' + item.route)
                                    : '#'
                            "
                            :icon="item.icon"
                            :label="item.label"
                            :active="isActive(item)"
                        />
                    </template>
                </SidebarItemExpand>
            </template>
        </div>
    </nav>
</template>
<script setup>
import { router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import SidebarItemExpand from "./SidebarItemExpand.vue";
import SidebarItem from "./SidebarItem.vue";
import {
    faBox,
    faBoxes,
    faCartShopping,
    faChartLine,
    faChartPie,
    faHashtag,
    faReceipt,
    faUsers,
    faUserTie,
    faWallet,
} from "@fortawesome/free-solid-svg-icons";

const activeMenu = computed(() => {
    const _ = usePage().url;
    return route().current();
});

const normalizeRoute = (name) => {
    return name?.endsWith(".index") ? name.slice(0, -6) : name;
};

const isActive = (menu) => {
    const current = normalizeRoute(activeMenu.value);

    if (menu.items) {
        return menu.items.some((child) =>
            current.startsWith("dashboard." + normalizeRoute(child.route))
        );
    }

    return current.startsWith("dashboard." + normalizeRoute(menu.route));
};

const sidebars = [
    {
        route: "overview",
        icon: faChartPie,
        label: "Ringkasan",
        activeRoute: "dashboard.overview",
    },
    {
        route: "#",
        icon: faReceipt,
        label: "Penjualan",
        activeRoute: "sales",
    },
    {
        route: "#",
        icon: faBox,
        label: "Produk",
        activeRoute: "products.",
        items: [
            {
                route: "#",
                label: "Satuan",
                activeRoute: "products.units.",
            },
            {
                route: "#",
                label: "Kategori",
                activeRoute: "products.categories.",
            },
            {
                route: "#",
                label: "Varian",
                activeRoute: "products.variations.",
            },
            {
                route: "#",
                label: "Varian",
                activeRoute: "products.variations.",
            },
            {
                route: "#",
                label: "Data Produk",
                activeRoute: "products.products.",
            },
        ],
    },
    {
        route: "#",
        icon: faBoxes,
        label: "Inventori",
        activeRoute: "inventories.",
        items: [
            {
                route: "#",
                label: "Stok",
                activeRoute: "inventories.stock.",
            },
            {
                route: "#",
                label: "Pemasok / Supplier",
                activeRoute: "inventories.suppliers.",
            },
            {
                route: "#",
                label: "Pesanan Pembelian (PO)",
                activeRoute: "inventories.po.",
            },
            {
                route: "#",
                label: "Stok Opname",
                activeRoute: "inventories.stock-taking.",
            },
            {
                route: "#",
                label: "Stok Retur",
                activeRoute: "inventories.return.",
            },
            {
                route: "#",
                label: "Mutasi Stok",
                activeRoute: "inventories.transfers.",
            },
            {
                route: "#",
                label: "Konversi Stok",
                activeRoute: "inventories.conversion.",
            },
        ],
    },
    {
        route: "#",
        icon: faCartShopping,
        label: "Pesanan / Order",
        activeRoute: "orders",
    },
    {
        route: "#",
        icon: faUsers,
        label: "Pelanggan",
        activeRoute: "members",
    },
    {
        route: "employees.index",
        icon: faUserTie,
        label: "Pegawai",
        activeRoute: "employees",
    },
    {
        route: "#",
        icon: faWallet,
        label: "Keuangan",
        activeRoute: "finance",
        items: [
            {
                route: "#",
                label: "Penjualan Harian",
                activeRoute: "finance.daily-sales.",
            },
            {
                route: "#",
                label: "Pembayaran",
                activeRoute: "finance.payments.",
            },
            {
                route: "#",
                label: "Pengembalian Dan Diskon",
                activeRoute: "finance.refund-discount.",
            },
        ],
    },
    {
        route: "#",
        icon: faChartLine,
        label: "Laporan",
        activeRoute: "reports",
        items: [
            {
                route: "#",
                label: "Penjualan Produk",
                activeRoute: "reports.products.",
            },
            {
                route: "#",
                label: "Stok",
                activeRoute: "template.form",
            },
            {
                route: "#",
                label: "Pegawai",
                activeRoute: "reports.employees.",
            },
            {
                route: "#",
                label: "Retur",
                activeRoute: "reports.return.",
            },
            {
                route: "#",
                label: "Pajak & Diskon",
                activeRoute: "reports.tax-discounts.",
            },
            {
                route: "#",
                label: "Omset",
                activeRoute: "reports.revenue.",
            },
        ],
    },
    {
        route: "#",
        icon: faHashtag,
        label: "Template",
        activeRoute: "template",
        items: [
            {
                route: "template.form",
                label: "Form",
                activeRoute: "template.form",
            },
            {
                route: "template.cards",
                label: "Card",
                activeRoute: "template.form",
            },
            {
                route: "template.navigation",
                label: "Navigation & Tab",
                activeRoute: "template.navigation",
            },
            {
                route: "template.buttons",
                label: "Buttons",
                activeRoute: "template.buttons",
            },
            {
                route: "template.charts",
                label: "Charts",
                activeRoute: "template.charts",
            },
            {
                route: "template.notifications",
                label: "Notifications",
                activeRoute: "template.notifications",
            },
            {
                route: "template.widgets",
                label: "Widgets",
                activeRoute: "template.widgets",
            },
        ],
    },
];
</script>
