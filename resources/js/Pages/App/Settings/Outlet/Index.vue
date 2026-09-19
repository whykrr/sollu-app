<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Data Outlet" />
        </template>

        <template #filter>
            <Filter :filters="params" @create="handleAddOutlet" />
        </template>

        <!-- Modal Tagihan Penambahan Outlet Belum Dibayar -->
        <UnpaidInvoiceModal
            :show="showUnpaidModal"
            :unpaid-invoice="unpaidInvoice"
            @close="showUnpaidModal = false"
        />

        <!-- Outlets Grid List -->
        <div
            v-if="outlets?.data && outlets.data.length > 0"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
        >
            <div
                v-for="outlet in outlets.data"
                :key="outlet.id"
                class="bg-white rounded-xl border border-slate-200 shadow-xs p-5 flex flex-col justify-between transition-all hover:shadow-md"
            >
                <div>
                    <!-- Header Card -->
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div
                                class="size-9 rounded-lg flex items-center justify-center shrink-0"
                                :class="
                                    outlet.is_main_outlet
                                        ? 'bg-amber-100 text-amber-600'
                                        : 'bg-main/10 text-main'
                                "
                            >
                                <FontAwesomeIcon :icon="faStore" />
                            </div>
                            <div class="min-w-0">
                                <h4
                                    class="font-semibold text-slate-800 text-sm leading-snug truncate"
                                    :title="outlet.name"
                                >
                                    {{ outlet.name }}
                                </h4>
                                <span
                                    v-if="outlet.is_main_outlet"
                                    class="text-xs font-medium text-amber-600 flex items-center gap-1"
                                >
                                    <FontAwesomeIcon :icon="faStar" class="text-[10px]" />
                                    Outlet Utama
                                </span>
                                <span v-else class="text-xs text-slate-500"> Cabang </span>
                            </div>
                        </div>
                        <span
                            v-if="outlet.is_active"
                            class="badge badge-success text-[11px] font-semibold shrink-0 inline-flex items-center gap-1"
                        >
                            <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Aktif
                        </span>
                        <span
                            v-else
                            class="badge badge-danger text-[11px] font-semibold shrink-0 inline-flex items-center gap-1"
                        >
                            <span class="size-1.5 rounded-full bg-rose-500"></span>
                            Nonaktif
                        </span>
                    </div>

                    <!-- Metadata Body -->
                    <div
                        class="space-y-1.5 text-xs text-slate-600 py-2 border-t border-b border-slate-100 my-3"
                    >
                        <div class="flex justify-between gap-2">
                            <span class="text-slate-400 shrink-0">Alamat:</span>
                            <span
                                class="text-slate-700 text-right line-clamp-2"
                                :title="outlet.address || '-'"
                            >
                                {{ outlet.address || '-' }}
                            </span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-slate-400 shrink-0">Telepon:</span>
                            <span class="text-slate-700 font-mono">
                                {{ outlet.phone || '-' }}
                            </span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-slate-400 shrink-0">Email:</span>
                            <span
                                class="text-slate-700 truncate max-w-[180px]"
                                :title="outlet.email || '-'"
                            >
                                {{ outlet.email || '-' }}
                            </span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-slate-400 shrink-0">Dibuat:</span>
                            <span class="text-slate-700">
                                {{ formatDateTimeSimple(outlet.created_at) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Footer Card Action Area -->
                <div class="flex items-center justify-between gap-2 pt-2">
                    <!-- Left: Main Outlet status / trigger -->
                    <span
                        v-if="outlet.is_main_outlet"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200/80 px-2.5 py-1.5 rounded-lg"
                    >
                        <FontAwesomeIcon :icon="faStar" />
                        <span>Outlet Utama</span>
                    </span>
                    <button
                        v-else
                        type="button"
                        class="btn btn-outline-main btn-sm text-xs rounded-lg px-2.5 py-1.5 flex items-center gap-1.5 transition-colors"
                        :disabled="!outlet.is_active"
                        :title="
                            !outlet.is_active
                                ? 'Aktifkan outlet terlebih dahulu untuk menjadikannya outlet utama'
                                : 'Jadikan Outlet Utama'
                        "
                        @click="confirmSetMainOutlet(outlet)"
                    >
                        <FontAwesomeIcon :icon="faStar" />
                        <span>Jadikan Outlet Utama</span>
                    </button>

                    <!-- Right: Edit & Status actions -->
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            class="btn btn-highlight-main btn-sm rounded-lg"
                            title="Ubah Data Outlet"
                            @click="openEdit(outlet)"
                        >
                            <FontAwesomeIcon :icon="faPencil" />
                        </button>

                        <template v-if="!outlet.is_main_outlet">
                            <button
                                v-if="outlet.is_active"
                                type="button"
                                class="btn btn-highlight-success btn-sm rounded-lg"
                                title="Nonaktifkan Outlet"
                                @click="disabledOutlet(outlet.id)"
                            >
                                <FontAwesomeIcon :icon="faToggleOff" />
                            </button>
                            <button
                                v-else
                                type="button"
                                class="btn btn-highlight-danger btn-sm rounded-lg"
                                title="Aktifkan Outlet"
                                @click="enabledOutlet(outlet.id)"
                            >
                                <FontAwesomeIcon :icon="faToggleOn" />
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="bg-white rounded-xl border border-slate-200 p-12 text-center flex flex-col items-center justify-center"
        >
            <div
                class="size-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mb-4"
            >
                <FontAwesomeIcon :icon="faStore" />
            </div>
            <h3 class="text-base font-semibold text-slate-800 mb-1">Belum Ada Outlet Terdaftar</h3>
            <p class="text-xs text-slate-500 max-w-sm mb-6">
                Daftarkan cabang atau outlet baru untuk mengelola operasional dan transaksi bisnis
                Anda.
            </p>
            <button
                class="btn btn-main px-4 py-2 rounded-lg flex items-center gap-2"
                @click="handleAddOutlet"
            >
                <FontAwesomeIcon :icon="faPlus" />
                <span>Tambah Outlet Sekarang</span>
            </button>
        </div>
        <template #footer>
            <Pagination
                :links="outlets.links"
                :from="outlets.from"
                :to="outlets.to"
                :total="outlets.total"
                :per-page="outlets.per_page ?? 20"
            />
        </template>
    </MainPage>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faPencil,
    faPlus,
    faStar,
    faStore,
    faToggleOff,
    faToggleOn,
} from '@fortawesome/free-solid-svg-icons'

import MainPage from '@/Components/UI/MainPage.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import Filter from './Components/Filter.vue'
import Wizard from './Components/Wizard.vue'
import UnpaidInvoiceModal from './Components/UnpaidInvoiceModal.vue'
import EditOutletPopUp from './Components/EditOutletPopUp.vue'
import { formatDateTimeSimple } from '@/Composable/date'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification'

const popUpStore = usePopUpStore()
const modalStore = useModalStore()

const props = defineProps({
    outlets: Object,
    params: Object,
    subscription: Object,
    proratedAmount: Number,
})

const showUnpaidModal = ref(false)
const unpaidInvoice = ref({ number: '', url: '' })

const handleAddOutlet = () => {
    popUpStore.open({
        title: 'Tambahkan Outlet Baru',
        size: 'lg',
        component: Wizard,
        props: {
            subscription: props.subscription,
            proratedAmount: props.proratedAmount,
        },
    })
}

const openEdit = outlet => {
    popUpStore.open({
        title: 'Ubah Data Outlet',
        component: EditOutletPopUp,
        props: {
            outlet,
        },
    })
}

const confirmSetMainOutlet = outlet => {
    if (!outlet.is_active) return

    modalStore.confirm({
        title: 'Jadikan Outlet Utama?',
        message: `Apakah Anda yakin ingin menetapkan "${outlet.name}" sebagai Outlet Utama? Status outlet utama pada cabang sebelumnya akan dialihkan.`,
        confirmText: 'Ya, Jadikan Utama',
        cancelText: 'Batal',
        onConfirm: () => {
            router.put(
                route('settings.outlets.set-main', { outlet: outlet.id }),
                {},
                {
                    preserveScroll: true,
                    only: ['outlets', 'flash'],
                }
            )
        },
    })
}

const disabledOutlet = id => {
    router.delete(route('settings.outlets.disabled', { outlet: id }), {
        only: ['outlets'],
        preserveState: true,
        preserveScroll: true,
    })
}

const enabledOutlet = id => {
    router.put(
        route('settings.outlets.enabled', { outlet: id }),
        {},
        {
            only: ['outlets', 'errors'],
            preserveState: true,
            preserveScroll: true,
            onError: errors => {
                if (errors.unpaid_invoice_number) {
                    unpaidInvoice.value = {
                        number: errors.unpaid_invoice_number,
                        url: errors.unpaid_invoice_url,
                    }
                    showUnpaidModal.value = true
                }
            },
        }
    )
}
</script>
