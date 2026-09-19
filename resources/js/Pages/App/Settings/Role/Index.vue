<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Peran & Hak Akses" />
        </template>

        <template #filter>
            <Filter
                :filters="filters"
                :can-create="can($enums.PermissionEnum?.ROLE_CREATE)"
                @create="openCreate"
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

                    <template #is_default="{ row }">
                        <span
                            class="badge text-xs font-medium"
                            :class="row.is_default ? 'badge-info' : 'badge-neutral-500'"
                        >
                            {{ row.is_default ? 'Bawaan Sistem' : 'Kustom' }}
                        </span>
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

const headers = [
    { field: 'name', label: 'Nama Peran', slot: 'name' },
    { field: 'is_default', label: 'Tipe', slot: 'is_default' },
    { field: 'users_count', label: 'Pengguna', slot: 'users_count' },
]

defineProps({
    roles: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
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
