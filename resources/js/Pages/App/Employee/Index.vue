<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Daftar Pegawai"
                description="Kelola hak akses kasir, manajer, staf outlet, dan otentikasi operasional bisnismu."
            />
        </template>

        <template #filter>
            <EmployeeFilter
                :filters="params"
                :roles="roles"
                @create="openCreate()"
                @open-import="showImportModal = true"
            />
        </template>

        <Table
            :headers="tableHeaders"
            :data="users.data"
            :sort="params.sort ?? 'created_at'"
            :sort-direction="params.direction ?? 'desc'"
            :action="true"
            @row-click="openDetail"
        >
            <template #name="{ row }">
                <div class="flex items-center gap-2">
                    <div
                        class="w-7 h-7 rounded-full bg-slate-100 text-slate-700 font-semibold flex items-center justify-center text-xs shrink-0"
                    >
                        {{ getInitials(row.name) }}
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="font-semibold text-slate-800 text-xs">{{ row.name }}</span>
                            <span
                                v-if="row.is_root_user"
                                class="badge badge-warning text-[10px] p-0.5 px-1.5"
                            >
                                Root
                            </span>
                            <span
                                v-if="row.deleted_at"
                                class="badge badge-neutral-500 text-[10px] p-0.5 px-1.5"
                            >
                                Arsip
                            </span>
                        </div>
                        <div class="text-[11px] text-slate-400 truncate">
                            {{ row.email }}
                        </div>
                    </div>
                </div>
            </template>

            <template #roles="{ row }">
                <span v-if="row.is_root_user" class="badge badge-warning text-xs"> Owner </span>
                <span
                    v-else-if="row.roles && row.roles.length > 0"
                    class="badge badge-info text-xs"
                >
                    {{ row.roles[0].label || row.roles[0].name }}
                </span>
                <span v-else class="text-xs text-slate-400">-</span>
            </template>

            <template #outlets="{ row }">
                <div v-if="row.is_root_user" class="text-xs text-slate-500">
                    <span class="badge badge-success text-xs">Semua Outlet</span>
                </div>
                <div v-else-if="row.outlets && row.outlets.length > 0" class="flex flex-wrap gap-1">
                    <span
                        v-for="(outlet, index) in row.outlets.slice(0, 2)"
                        :key="index"
                        class="badge text-xs badge-neutral-500 text-nowrap"
                    >
                        {{ outlet.name }}
                    </span>
                    <span
                        v-if="row.outlets.length > 2"
                        class="badge text-xs badge-neutral-500 text-nowrap"
                    >
                        +{{ row.outlets.length - 2 }}
                    </span>
                </div>
                <span v-else class="text-xs text-slate-400">-</span>
            </template>

            <template #created_at="{ row }">
                <span class="text-xs text-slate-600">
                    {{ formatDateTimeSimple(row.created_at) }}
                </span>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-1" @click.stop>
                    <button
                        class="btn btn-flat btn-sm"
                        title="Detail Pegawai"
                        @click="openDetail(row)"
                    >
                        <FontAwesomeIcon :icon="faEye" />
                    </button>

                    <button
                        v-if="!row.deleted_at"
                        class="btn btn-flat btn-sm"
                        title="Ubah Data"
                        @click="openEdit(row)"
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
                </div>
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

        <!-- Modal Impor Data Massal Excel -->
        <ImportCsvModal
            :show="showImportModal"
            module-name="Pegawai"
            :template-url="route('employees.importTemplate')"
            :import-url="route('employees.import')"
            @close="showImportModal = false"
        />
    </MainPage>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import ButtonIconGroupArchive from '@/Components/Button/ButtonIconGroupArchive.vue'
import ImportCsvModal from '@/Components/Modals/ImportCsvModal.vue'
import EmployeeFilter from '@/Pages/App/Employee/Components/EmployeeFilter.vue'
import EmployeeFormPopUp from '@/Pages/App/Employee/Components/EmployeeFormPopUp.vue'
import EmployeeDetailPopUp from '@/Pages/App/Employee/Components/EmployeeDetailPopUp.vue'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification'
import { formatDateTimeSimple } from '@/Composable/date'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPencil, faEye } from '@fortawesome/free-solid-svg-icons'

const popUpStore = usePopUpStore()
const modalStore = useModalStore()
const showImportModal = ref(false)

const props = defineProps({
    users: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    params: {
        type: Object,
        default: () => ({}),
    },
    roles: {
        type: [Object, Array],
        default: () => [],
    },
})

const nonOwnerRoles = computed(() => {
    if (!props.roles) return []
    const rolesList = Array.isArray(props.roles) ? props.roles : Object.values(props.roles)
    return rolesList.filter(r => (r.value ?? r.name) !== 'owner')
})

const tableHeaders = [
    { field: 'name', label: 'Nama Pegawai', slot: 'name', sortable: true },
    { field: 'roles', label: 'Peran', slot: 'roles' },
    { field: 'outlets', label: 'Akses Outlet', slot: 'outlets', show: 'md' },
    {
        field: 'created_at',
        label: 'Terdaftar',
        sortable: true,
        slot: 'created_at',
        show: 'lg',
    },
]

const getInitials = name => {
    if (!name) return '?'
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .substring(0, 2)
        .toUpperCase()
}

const openCreate = () => {
    if (nonOwnerRoles.value.length === 0) {
        modalStore.open({
            type: 'warning',
            title: 'Yuk, Siapkan Peran Pegawai Terlebih Dahulu 👋',
            message:
                'Saat ini bisnis Anda baru memiliki peran Pemilik Usaha (Owner). Memberikan peran Owner ke staf akan membuka seluruh wewenang bisnis. Sebaiknya siapkan peran khusus pegawai terlebih dahulu (tersedia template siap pakai!).',
            confirmText: '⚡ Buat Peran via Template',
            cancelText: 'Tetap Lanjut Jadi Owner',
            confirmClass: 'btn-main',
            onConfirm: () => {
                router.visit(route('settings.roles.index'))
            },
            onCancel: () => {
                popUpStore.open({
                    title: 'Tambah Pegawai Baru',
                    size: 'md',
                    component: EmployeeFormPopUp,
                    props: { roles: props.roles },
                })
            },
        })
        return
    }

    popUpStore.open({
        title: 'Tambah Pegawai Baru',
        size: 'md',
        component: EmployeeFormPopUp,
        props: { roles: props.roles },
    })
}

const openEdit = user => {
    popUpStore.open({
        title: 'Ubah Data Pegawai',
        subTitle: '#' + user.email,
        size: 'md',
        component: EmployeeFormPopUp,
        props: { user, roles: props.roles },
    })
}

const openDetail = user => {
    popUpStore.open({
        title: 'Detail Pegawai',
        subTitle: '#' + user.email,
        size: 'lg',
        component: EmployeeDetailPopUp,
        props: { user, roles: props.roles },
        events: {
            edit: targetUser => {
                openEdit(targetUser)
            },
        },
    })
}
</script>
