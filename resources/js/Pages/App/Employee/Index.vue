<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Data Pegawai" />
        </template>

        <template #filter>
            <Filter :filters="params" :roles="roles" @create="openForm()" />
        </template>

        <Table
            :headers="tableHeaders"
            :data="users.data"
            :sort="params.sort ?? 'updated_at'"
            :sort-direction="params.direction ?? 'desc'"
            :action="true"
        >
            <template #name="{ row }">
                {{ row.name }}
                <span v-if="row.deleted_at" class="badge badge-neutral-500 p-1 text-xs">Arsip</span>
                <span v-if="row.is_root_user" class="badge badge-warning p-1 text-xs">Root</span>
            </template>
            <template #roles="{ row }">
                {{ row.roles[0].label }}
            </template>
            <template #outlets="{ row }">
                <div class="space-x-0.5">
                    <label
                        v-for="(outlet, index) in row.outlets.slice(0, 2)"
                        :key="index"
                        class="badge text-sm badge-info text-nowrap"
                        >{{ outlet.name }}</label
                    >
                    <label
                        v-if="row.outlets.length > 2"
                        class="badge text-sm badge-info text-nowrap"
                        >+{{ row.outlets.length - 2 }} Lainnya</label
                    >
                </div>
            </template>
            <template #created_at="{ row }">
                {{ formatDateTimeSimple(row.created_at) }}
            </template>
            <template #actions="{ row }">
                <button
                    v-if="!row.deleted_at"
                    class="btn btn-highlight-main btn-sm"
                    title="Ubah"
                    @click="getDetail(row.id)"
                >
                    <FontAwesomeIcon :icon="faPencil" />
                </button>

                <ButtonIconGroupArchive
                    v-if="!row.is_root_user"
                    :data="row"
                    :url-delete="
                        route('employees.delete', {
                            user: row.id,
                            ...props.params,
                        })
                    "
                    :url-restore="
                        route('employees.restore', {
                            user: row.id,
                            ...props.params,
                        })
                    "
                    :url-destroy="
                        route('employees.destroy', {
                            user: row.id,
                            ...props.params,
                        })
                    "
                />
            </template>
        </Table>
        <template #footer>
            <Pagination
                :links="users.links"
                :from="users.from"
                :to="users.to"
                :total="users.total"
                :per-page="users.per_page ?? 20"
            />
        </template>
    </MainPage>
</template>

<script setup>
import { computed } from 'vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import Filter from '@/Pages/App/Employee/Components/Filter.vue'
import { router } from '@inertiajs/vue3'
import MainPage from '@/Components/UI/MainPage.vue'
import Table from '@/Components/Tables/Table.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPencil } from '@fortawesome/free-solid-svg-icons'
import { formatDateTimeSimple } from '@/Composable/date'
import Form from '@/Pages/App/Employee/Components/Form.vue'
import ButtonIconGroupArchive from '@/Components/Button/ButtonIconGroupArchive.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification'

const popUpStore = usePopUpStore()
const modalStore = useModalStore()

const props = defineProps({
    users: Object,
    params: Object,
    roles: [Object, Array],
    user: Object,
})

const nonOwnerRoles = computed(() => {
    if (!props.roles) return []
    const rolesList = Array.isArray(props.roles) ? props.roles : Object.values(props.roles)
    return rolesList.filter(r => (r.value ?? r.name) !== 'owner')
})

const openFormDirect = (user = null) => {
    popUpStore.open({
        title: user ? 'Detail karyawan' : 'Tambahkan karyawan baru',
        subTitle: user ? '#' + user.email : null,
        size: 'lg',
        component: Form,
        props: { user, roles: props.roles },
    })
}

const openForm = (user = null) => {
    if (user) {
        openFormDirect(user)
        return
    }

    if (nonOwnerRoles.value.length === 0) {
        modalStore.open({
            type: 'warning',
            title: 'Yuk, Siapkan Peran Karyawan Terlebih Dahulu 👋',
            message:
                'Saat ini bisnis Anda baru memiliki peran Pemilik Usaha (Owner). Memberikan peran Owner ke staf akan membuka seluruh wewenang bisnis, termasuk laporan omzet rahasia, pengaturan pembayaran, hingga hak kelola akun. Sebaiknya siapkan peran khusus karyawan terlebih dahulu (tersedia template siap pakai!).',
            confirmText: '⚡ Buat Peran via Template',
            cancelText: 'Tetap Lanjut Jadi Owner',
            confirmClass: 'btn-main',
            onConfirm: () => {
                router.visit(route('settings.roles.index'))
            },
            onCancel: () => {
                openFormDirect(null)
            },
        })
        return
    }

    openFormDirect(null)
}

if (props.user) {
    openFormDirect(props.user)
}

const tableHeaders = [
    { field: 'name', label: 'Nama', slot: 'name', sortable: true },
    { field: 'roles', label: 'Peran', slot: 'roles' },
    { field: 'outlets', label: 'Outlet', slot: 'outlets', show: 'lg' },
    {
        field: 'created_at',
        label: 'Dibuat',
        sortable: true,
        slot: 'created_at',
    },
]

const getDetail = id => {
    router.visit(route('employees.show', { user: id, ...props.params }), {
        only: ['user'],
        preserveState: true,
        preserveScroll: true,
        onSuccess: page => {
            openForm(page.props.user)
        },
    })
}
</script>
