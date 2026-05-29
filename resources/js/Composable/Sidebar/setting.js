import { faCog, faCreditCard, faGears, faHistory, faInfo, faMapMarkerAlt, faMarker, faMoneyBill, faMoneyBills, faReceipt, faShop, faUserShield, faWallet, faXmarkCircle } from '@fortawesome/free-solid-svg-icons'

export const settingSidebars = [
    {
        label: 'Usaha',
        permissions: 'business.info',
    },
    {
        route: 'business.info.detail',
        icon: faShop,
        label: 'Detail Usaha',
        permissions: 'business.info',
        activeRoute: 'business.info.detail',
    },
    {
        route: 'business.outlets.index',
        icon: faMapMarkerAlt,
        label: 'Outlet',
        permissions: 'business.info',
        activeRoute: 'sales',
    },
    {
        label: 'Langganan & Tagihan',
        permissions: 'business.billing',
    },
    {
        route: 'business.billing.index',
        icon: faCreditCard,
        label: 'Langganan',
        permissions: 'business.billing',
        activeRoute: 'sales',
    },
    {
        route: 'business.invoices.index',
        icon: faMoneyBills,
        label: 'Pembayaran',
        permissions: 'business.billing',
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
