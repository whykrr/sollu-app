<template>
    <MainPage>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-neutral-800">Rekening Manual</h1>
                    <p class="text-sm text-neutral-500 mt-1">
                        Kelola rekening bank untuk pembayaran manual langganan.
                    </p>
                </div>
                <button class="btn btn-main" @click="openForm()">
                    <FontAwesomeIcon :icon="faPlus" class="mr-2" />
                    Tambah Rekening
                </button>
            </div>
        </template>

        <div class="bg-white rounded-xl shadow-sm border border-neutral-100 p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="method in methods"
                    :key="method.id"
                    class="border border-slate-200 bg-white rounded-lg p-4 transition-all hover:border-main/30 flex flex-col justify-between"
                >
                    <div>
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-xs font-bold text-blue-600 tracking-wider uppercase">
                                {{ method.bank_name }}
                            </span>
                            <span
                                class="px-2 py-0.5 text-[10px] rounded-full font-bold"
                                :class="
                                    method.is_active
                                        ? 'bg-success/10 text-success'
                                        : 'bg-neutral-200 text-neutral-600'
                                "
                            >
                                {{ method.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                        <span class="block text-xl font-bold text-gray-900 mt-1">
                            {{ method.account_number }}
                        </span>
                        <span class="block text-xs text-gray-400 mt-0.5 mb-4">
                            a/n {{ method.account_name }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
                        <button
                            class="btn btn-outline-main btn-xs flex-1"
                            @click="openForm(method)"
                        >
                            <FontAwesomeIcon :icon="faPencil" class="mr-1" />
                            Edit
                        </button>
                        <button
                            class="btn btn-xs flex-1"
                            :class="method.is_active ? 'btn-outline-danger' : 'btn-outline-success'"
                            @click="confirmToggle(method)"
                        >
                            <FontAwesomeIcon
                                :icon="method.is_active ? faBan : faCheck"
                                class="mr-1"
                            />
                            {{ method.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                        <button
                            class="btn btn-outline-danger btn-xs px-2"
                            @click="confirmDelete(method)"
                        >
                            <FontAwesomeIcon :icon="faTrash" />
                        </button>
                    </div>
                </div>

                <div
                    v-if="!methods || methods.length === 0"
                    class="col-span-full border border-dashed border-slate-300 rounded-lg p-8 text-center text-gray-500"
                >
                    <FontAwesomeIcon
                        :icon="faBuildingColumns"
                        class="text-3xl text-gray-300 mb-3"
                    />
                    <p class="text-sm font-medium text-gray-600">Belum ada data rekening manual.</p>
                    <button class="btn btn-main btn-sm mt-3" @click="openForm()">
                        Tambah Rekening Pertama
                    </button>
                </div>
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import MainPage from '@/Components/UI/MainPage.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faPlus,
    faPencil,
    faBan,
    faCheck,
    faTrash,
    faBuildingColumns,
} from '@fortawesome/free-solid-svg-icons'
import { router } from '@inertiajs/vue3'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification.js'
import FormPopUp from './Components/FormPopUp.vue'

const props = defineProps({
    methods: Array,
})

const popUpStore = usePopUpStore()
const modalStore = useModalStore()

const openForm = (method = null) => {
    popUpStore.open({
        title: method ? 'Edit Rekening Manual' : 'Tambah Rekening Manual',
        size: 'md',
        component: FormPopUp,
        props: {
            methodData: method,
        },
    })
}

const confirmToggle = method => {
    const isActivating = !method.is_active
    modalStore.confirm({
        title: isActivating ? 'Aktifkan Rekening' : 'Nonaktifkan Rekening',
        message: isActivating
            ? `Apakah Anda yakin ingin mengaktifkan kembali rekening ${method.bank_name}?`
            : `Apakah Anda yakin ingin menonaktifkan rekening ${method.bank_name}?`,
        type: isActivating ? 'info' : 'warning',
        confirmText: 'Ya, Lanjutkan',
        onConfirm: () => {
            router.post(
                route('cockpit.payment-methods.toggle-status', method.id),
                {},
                { preserveScroll: true }
            )
        },
    })
}

const confirmDelete = method => {
    modalStore.confirm({
        title: 'Hapus Rekening',
        message: `Apakah Anda yakin ingin menghapus rekening ${method.bank_name} secara permanen?`,
        type: 'danger',
        confirmText: 'Ya, Hapus',
        onConfirm: () => {
            router.delete(route('cockpit.payment-methods.destroy', method.id), {
                preserveScroll: true,
            })
        },
    })
}
</script>
