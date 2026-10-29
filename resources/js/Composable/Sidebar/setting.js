import { computed } from 'vue'
import {
    faBoxes,
    faCashRegister,
    faCreditCard,
    faMapMarkerAlt,
    faShop,
    faUserCircle,
    faUserShield,
    faSliders,
} from '@fortawesome/free-solid-svg-icons'

export const getSettingSidebars = (enums = { FeatureEnum: {} }) => [
    {
        type: 'item',
        url: route('settings.account.profile'),
        icon: faUserCircle,
        label: 'Pusat Akun',
        permissions: [],
        activeRoute: 'settings.account',
    },
    {
        type: 'section',
        label: 'Bisnis',
        permissions: ['business.view', 'business.billing'],
        separator: false,
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
        feature: enums.FeatureEnum.FEATURE_PERSONALIZATION,
        permissions: ['business.view'],
        activeRoute: 'settings.business.features',
    },
    {
        type: 'section',
        label: 'Operasional & Outlet',
        permissions: ['outlet.view', 'setting.device', 'business.view'],
    },
    {
        type: 'item',
        url: route('settings.outlets.index'),
        icon: faMapMarkerAlt,
        label: 'Outlet & Perangkat',
        permissions: ['outlet.view', 'setting.device'],
        activeRoute: ['settings.outlets', 'settings.operational', 'settings.devices'],
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
        type: 'section',
        label: 'Transaksi & Keamanan',
        permissions: [
            'setting.sales',
            'transaction.view',
            'setting.payment',
            'setting.receipt',
            'setting.tax',
            'role.view',
            'setting.audit',
        ],
    },
    {
        type: 'item',
        url: route('settings.sales.index'),
        icon: faCashRegister,
        label: 'Transaksi & Kasir',
        permissions: ['setting.sales', 'transaction.view', 'setting.tax', 'setting.receipt'],
        activeRoute: ['settings.sales', 'settings.taxes', 'settings.receipt'],
    },
    {
        type: 'item',
        url: route('settings.payment-methods.index'),
        icon: faCreditCard,
        label: 'Metode Pembayaran',
        permissions: ['setting.payment'],
        activeRoute: 'settings.payment-methods',
    },
    {
        type: 'item',
        url: route('settings.roles.index'),
        icon: faUserShield,
        label: 'Peran & Keamanan',
        permissions: ['role.view', 'setting.audit'],
        activeRoute: ['settings.roles', 'settings.activity-logs'],
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
