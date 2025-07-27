<template>
    <nav class="flex-1 sidebar">
        <div class="sidebar-container">
            <template v-for="sidebar in sidebars">
                <SidebarItem
                    v-if="!sidebar.items"
                    :to="sidebar.link"
                    :icon="sidebar.icon"
                    :label="sidebar.label"
                    :is-active="menuActive.includes(sidebar.activeRoute)"
                />
                <SidebarItemExpand
                    v-else
                    :to="sidebar.link"
                    :icon="sidebar.icon"
                    :label="sidebar.label"
                    :is-active="menuActive.includes(sidebar.activeRoute)"
                >
                    <template v-for="items in sidebar.items">
                        <SidebarItem
                            :to="items.link"
                            :icon="items.icon"
                            :label="items.label"
                            :is-active="menuActive.includes(items.activeRoute)"
                        />
                    </template>
                </SidebarItemExpand>
            </template>
        </div>
    </nav>
</template>
<script setup>
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import SidebarItemExpand from "./SidebarItemExpand.vue";
import SidebarItem from "./SidebarItem.vue";

const page = usePage();
const menuActive = computed(() => page.props.menuActive);
const sidebars = [
    {
        link: route("overview"),
        icon: "fa-chart-pie",
        label: "Ringkasan",
        activeRoute: "overview",
    },
    {
        link: "#",
        icon: "fa-receipt",
        label: "Penjualan",
        activeRoute: "sales",
    },
    {
        link: "#",
        icon: "fa-box",
        label: "Produk",
        activeRoute: "products.",
        items: [
            {
                link: "#",
                label: "Satuan",
                activeRoute: "products.units.",
            },
            {
                link: "#",
                label: "Kategori",
                activeRoute: "products.categories.",
            },
            {
                link: "#",
                label: "Varian",
                activeRoute: "products.variations.",
            },
            {
                link: "#",
                label: "Varian",
                activeRoute: "products.variations.",
            },
            {
                link: "#",
                label: "Data Produk",
                activeRoute: "products.products.",
            },
        ],
    },
    {
        link: "#",
        icon: "fa-boxes",
        label: "Inventori",
        activeRoute: "inventories.",
        items: [
            {
                link: "#",
                label: "Stok",
                activeRoute: "inventories.stock.",
            },
            {
                link: "#",
                label: "Pemasok / Supplier",
                activeRoute: "inventories.suppliers.",
            },
            {
                link: "#",
                label: "Pesanan Pembelian (PO)",
                activeRoute: "inventories.po.",
            },
            {
                link: "#",
                label: "Stok Opname",
                activeRoute: "inventories.stock-taking.",
            },
            {
                link: "#",
                label: "Stok Retur",
                activeRoute: "inventories.return.",
            },
            {
                link: "#",
                label: "Mutasi Stok",
                activeRoute: "inventories.transfers.",
            },
            {
                link: "#",
                label: "Konversi Stok",
                activeRoute: "inventories.conversion.",
            },
        ],
    },
    {
        link: "#",
        icon: "fa-cart-shopping",
        label: "Pesanan / Order",
        activeRoute: "orders",
    },
    {
        link: "#",
        icon: "fa-users",
        label: "Pelanggan",
        activeRoute: "members",
    },
    {
        link: "#",
        icon: "fa-user-tie",
        label: "Pegawai",
        activeRoute: "employees",
    },
    {
        link: "#",
        icon: "fa-wallet",
        label: "Keuangan",
        activeRoute: "finance",
        items: [
            {
                link: "#",
                label: "Penjualan Harian",
                activeRoute: "finance.daily-sales.",
            },
            {
                link: "#",
                label: "Pembayaran",
                activeRoute: "finance.payments.",
            },
            {
                link: "#",
                label: "Pengembalian Dan Diskon",
                activeRoute: "finance.refund-discount.",
            },
        ],
    },
    {
        link: "#",
        icon: "fa-chart-line",
        label: "Laporan",
        activeRoute: "reports",
        items: [
            {
                link: "#",
                label: "Penjualan Produk",
                activeRoute: "reports.products.",
            },
            {
                link: "#",
                label: "Stok",
                activeRoute: "reports.stocks.",
            },
            {
                link: "#",
                label: "Pegawai",
                activeRoute: "reports.employees.",
            },
            {
                link: "#",
                label: "Retur",
                activeRoute: "reports.return.",
            },
            {
                link: "#",
                label: "Pajak & Diskon",
                activeRoute: "reports.tax-discounts.",
            },
            {
                link: "#",
                label: "Omset",
                activeRoute: "reports.revenue.",
            },
        ],
    },
];
</script>
