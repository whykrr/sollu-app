<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Peran & Hak Akses"
                description="Buat dan atur peran karyawan beserta hak akses yang sesuai untuk tiap posisi."
            />
        </template>

        <template #filter>
            <Filter
                :filters="filters"
                :can-create="can($enums.PermissionEnum?.ROLE_CREATE)"
                @create="openCreate"
                @open-template="openTemplate"
            />
        </template>

        <FeatureLock
            :feature="$enums.FeatureEnum.ROLE_PERMISSIONS"
            class="h-full flex-1 min-h-0 flex flex-col"
            content-class="h-full flex-1 min-h-0 flex flex-col"
        >
            <div class="h-full flex-1 min-h-0 flex flex-col">
                <Table :headers="headers" :data="roles" :action="true">
                    <template #name="{ row }">
                        <div class="flex flex-col">
                            <span class="font-medium text-neutral-900">{{ row.label }}</span>
                            <span class="text-xs text-neutral-500">{{ row.name }}</span>
                        </div>
                    </template>

                    <template #permissions_summary="{ row }">
                        <div v-if="row.name === $enums.RoleEnum?.OWNER || row.name === 'owner'">
                            <span class="badge badge-success text-xs font-medium">
                                Akses Penuh (Semua Modul)
                            </span>
                        </div>
                        <div v-else class="flex items-center gap-1.5 flex-wrap">
                            <span class="badge badge-info text-xs font-semibold">
                                {{ row.permissions_count }} Izin
                            </span>
                            <span
                                v-for="(grp, idx) in (row.summary_groups || []).slice(0, 3)"
                                :key="idx"
                                class="text-[11px] text-neutral-600 bg-slate-100 border border-slate-200/60 px-1.5 py-0.5 rounded"
                            >
                                {{ grp.label }} ({{ grp.count }})
                            </span>
                            <span
                                v-if="row.summary_groups && row.summary_groups.length > 3"
                                class="text-[11px] text-neutral-400 bg-slate-50 px-1 py-0.5 rounded"
                            >
                                +{{ row.summary_groups.length - 3 }} lainnya
                            </span>
                        </div>
                    </template>

                    <template #users_count="{ row }">
                        <span class="text-neutral-600"> {{ row.users_count }} Pengguna </span>
                    </template>

                    <template #actions="{ row }">
                        <button
                            v-if="can($enums.PermissionEnum?.ROLE_UPDATE)"
                            type="button"
                            class="btn btn-flat btn-sm h-8 w-8 !p-0 inline-flex items-center justify-center"
                            title="Ubah Peran"
                            @click="openEdit(row)"
                        >
                            <FontAwesomeIcon :icon="faPencil" />
                        </button>
                        <button
                            v-if="!row.is_default && can($enums.PermissionEnum?.ROLE_DELETE)"
                            type="button"
                            class="btn btn-flat btn-sm h-8 w-8 !p-0 inline-flex items-center justify-center text-danger hover:bg-danger/10"
                            :title="
                                row.users_count > 0
                                    ? 'Tidak dapat dihapus karena masih digunakan oleh pengguna'
                                    : 'Hapus Peran'
                            "
                            :disabled="row.users_count > 0"
                            @click="openDelete(row)"
                        >
                            <FontAwesomeIcon :icon="faTrash" />
                        </button>
                    </template>
                </Table>
            </div>
        </FeatureLock>
    </MainPage>
</template>

<script setup>
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification'
import { useAuth } from '@/Composable/useAuth'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPencil, faTrash } from '@fortawesome/free-solid-svg-icons'

import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import FeatureLock from '@/Components/UI/FeatureLock.vue'
import Filter from './Components/Filter.vue'
import RoleFormPopUp from './Components/RoleFormPopUp.vue'
import RoleTemplatePopUp from './Components/RoleTemplatePopUp.vue'

const headers = [
    { field: 'name', label: 'Nama Peran', slot: 'name' },
    { field: 'permissions_summary', label: 'Ringkasan Hak Akses', slot: 'permissions_summary' },
    { field: 'users_count', label: 'Pengguna', slot: 'users_count' },
]

const props = defineProps({
    roles: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    templates: {
        type: Array,
        default: () => [],
    },
    businessType: {
        type: String,
        default: 'general',
    },
})

const popUpStore = usePopUpStore()
const modalStore = useModalStore()
const { can } = useAuth()

const openCreate = () => {
    popUpStore.open({
        title: 'Tambah Peran Kustom',
        size: '2xl',
        component: RoleFormPopUp,
    })
}

const openTemplate = () => {
    popUpStore.open({
        title: 'Template Peran Siap Pakai',
        size: '2xl',
        component: RoleTemplatePopUp,
        props: {
            templates: props.templates,
            businessType: props.businessType,
        },
    })
}

const openEdit = role => {
    popUpStore.open({
        title: 'Ubah Peran & Hak Akses',
        size: '2xl',
        component: RoleFormPopUp,
        props: {
            role,
        },
    })
}

const openDelete = role => {
    modalStore.openModalDelete(
        route('settings.roles.destroy', role.id),
        'Hapus Peran Kustom',
        `Apakah Anda yakin ingin menghapus peran "${role.label}"? Karyawan yang menggunakan peran ini tidak akan bisa login sampai ditetapkan peran baru.`
    )
}
</script>
