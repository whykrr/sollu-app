import { faCog, faCreditCard, faGears, faHistory, faInfo, faMapMarkerAlt, faMarker, faMoneyBill, faMoneyBills, faReceipt, faShop, faUserShield, faWallet, faXmarkCircle } from '@fortawesome/free-solid-svg-icons'

export const settingSidebars = [
    {
        label: 'Usaha',
        permissions: 'merchant.info',
    },
    {
        route: 'merchant.info.detail',
        icon: faShop,
        label: 'Detail Usaha',
        permissions: 'merchant.info',
        activeRoute: 'merchant.info.detail',
    },
    {
        route: 'merchant.outlets.index',
        icon: faMapMarkerAlt,
        label: 'Outlet',
        permissions: 'merchant.info',
        activeRoute: 'sales',
    },
    {
        label: 'Langganan & Tagihan',
        permissions: 'merchant.billing',
    },
    {
        route: 'merchant.billing.index',
        icon: faCreditCard,
        label: 'Langganan',
        permissions: 'merchant.billing',
        activeRoute: 'sales',
    },
    {
        route: 'merchant.invoices.index',
        icon: faMoneyBills,
        label: 'Pembayaran',
        permissions: 'merchant.billing',
        activeRoute: 'sales',
    },
    {
        label: 'Pengaturan',
    },
    {
        route: '#',
        icon: faCog,
        label: 'Konfigurasi Umum',
        permissions: '',
        activeRoute: 'sales',
    },
    {
        route: '#',
        icon: faUserShield,
        label: 'Hak Akses',
        permissions: '',
        activeRoute: 'sales',
    },
    {
        route: '#',
        icon: faReceipt,
        label: 'Pembayaran',
        permissions: '',
        activeRoute: 'sales',
    },
]
