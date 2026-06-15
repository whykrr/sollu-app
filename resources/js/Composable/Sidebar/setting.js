import { faArrowLeft, faBoxes, faCashRegister, faCog, faCreditCard, faGears, faHistory, faHome, faInfo, faMapMarkerAlt, faMarker, faMoneyBill, faMoneyBills, faReceipt, faShop, faUser, faUserCircle, faUserShield, faWallet, faXmarkCircle } from '@fortawesome/free-solid-svg-icons'

export const settingSidebars = [
    {
        route: 'overview',
        icon: faArrowLeft,
        label: 'Kembali',
    },
    {
        label: 'Pengaturan',
    },
    {
        route: 'settings.account.profile',
        icon: faUserCircle,
        label: 'Pusat Akun',
        activeRoute: '',
    },
    {
        label: 'Usaha & Langganan',
        separator: true,
    },
    {
        route: 'settings.business.detail',
        icon: faShop,
        label: 'Detail Usaha',
        permissions: 'business.view',
        activeRoute: 'settings.business.detail',
    },
    {
        route: 'settings.outlets.index',
        icon: faMapMarkerAlt,
        label: 'Outlet',
        permissions: 'outlet.view',
        activeRoute: 'settings.outlets',
    },
    {
        route: 'settings.billing.index',
        icon: faCreditCard,
        label: 'Langganan',
        permissions: 'business.billing',
        activeRoute: 'sales',
    },
    {
        label: 'Pengaturan Umum',
        separator: true,
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
        icon: faCashRegister,
        label: 'Pengaturan Kasir',
        permissions: '',
        activeRoute: 'sales',
    },
    {
        route: '#',
        icon: faReceipt,
        label: 'Nota',
        permissions: '',
        activeRoute: 'sales',
    },
    {
        route: '#',
        icon: faBoxes,
        label: 'Inventori',
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

]
