import { computed } from 'vue'
import {
    faBoxes,
    faCalculator,
    faCashRegister,
    faClock,
    faCreditCard,
    faHistory,
    faMapMarkerAlt,
    faReceipt,
    faShop,
    faUserCircle,
    faUserShield,
    faSliders,
} from '@fortawesome/free-solid-svg-icons'

export const getSettingSidebars = (enums = { FeatureEnum: {} }) => [
    {
        type: 'section',
        label: 'Akun & Bisnis',
        separator: false,
    },
    {
        type: 'item',
        url: route('settings.account.profile'),
        icon: faUserCircle,
        label: 'Pusat Akun',
        permissions: [],
        activeRoute: 'settings.account',
    },
    {
        type: 'item',
        url: route('settings.business.detail'),
        icon: faShop,
        label: 'Informasi Usaha',
        permissions: ['business.view'],
        activeRoute: 'settings.business.detail',
    },
    {
        type: 'item',
        url: route('settings.billing.index'),
        icon: faCreditCard,
        label: 'Langganan & Tagihan',
        permissions: ['business.billing'],
        activeRoute: 'settings.billing',
    },
    {
        type: 'item',
        url: route('settings.business.features'),
        icon: faSliders,
        label: 'Personalisasi Fitur',
        permissions: ['business.update'],
        activeRoute: 'settings.business.features',
    },
    {
        type: 'section',
        label: 'Manajemen Outlet & Operasional',
        separator: true,
    },
    {
        type: 'item',
        url: route('settings.outlets.index'),
        icon: faMapMarkerAlt,
        label: 'Outlet',
        feature: enums.FeatureEnum.MULTI_OUTLET,
        permissions: ['outlet.view'],
        activeRoute: 'settings.outlets',
    },
    {
        type: 'item',
        url: route('settings.operational.index'),
        icon: faClock,
        label: 'Jam Operasional',
        feature: enums.FeatureEnum.OUTLET_MANAGEMENT,
        permissions: ['outlet.view'],
        activeRoute: 'settings.operational',
    },
    {
        type: 'item',
        url: route('settings.devices.index'),
        icon: faCashRegister,
        label: 'Perangkat',
        feature: enums.FeatureEnum.OUTLET_MANAGEMENT,
        permissions: ['setting.device', 'outlet.view'],
        activeRoute: 'settings.devices',
    },
    {
        type: 'item',
        url: route('settings.inventory.index'),
        icon: faBoxes,
        label: 'Manajemen Inventaris',
        feature: enums.FeatureEnum.INVENTORY_MANAGEMENT,
        permissions: ['business.view'],
        activeRoute: 'settings.inventory',
    },
    {
        type: 'item',
        url: route('settings.taxes.index'),
        icon: faCalculator,
        label: 'Pajak & Biaya',
        feature: enums.FeatureEnum.OUTLET_MANAGEMENT,
        permissions: ['setting.tax', 'outlet.view'],
        activeRoute: 'settings.taxes',
    },
    {
        type: 'section',
        label: 'Transaksi & Keamanan',
        separator: true,
    },
    {
        type: 'item',
        url: route('settings.payment-methods.index'),
        icon: faCreditCard,
        label: 'Metode Pembayaran',
        feature: enums.FeatureEnum.OUTLET_MANAGEMENT,
        permissions: ['setting.payment'],
        activeRoute: 'settings.payment-methods',
    },
    {
        type: 'item',
        url: route('settings.receipt.index'),
        icon: faReceipt,
        label: 'Layout Struk & Nota',
        feature: enums.FeatureEnum.OUTLET_MANAGEMENT,
        permissions: ['setting.receipt', 'outlet.view'],
        activeRoute: 'settings.receipt',
    },
    {
        type: 'item',
        url: route('settings.roles.index'),
        icon: faUserShield,
        label: 'Kustomisasi Peran & Hak Akses',
        feature: enums.FeatureEnum.CUSTOM_ROLE,
        permissions: ['role.view'],
        activeRoute: 'settings.roles',
    },
    {
        type: 'item',
        url: '#',
        icon: faHistory,
        label: 'Log Aktivitas',
        feature: enums.FeatureEnum.AUDIT_LOGS,
        permissions: [],
        activeRoute: 'settings.audit-logs',
    },
]

import { useEnum } from '@/Composable/useEnum'

export function useSettingSidebar() {
    const { enums } = useEnum()
    const settingSidebars = computed(() => getSettingSidebars(enums.value))

    return {
        settingSidebars,
        getSettingSidebars,
    }
}

export const settingSidebars = getSettingSidebars()
