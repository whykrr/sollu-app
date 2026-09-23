<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Konfigurasi Platform"
                description="Kelola pengaturan global sistem, saluran bantuan pengguna, dan integrasi gerbang pembayaran."
            />
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 pb-12">
            <!-- Left Column: Settings Form -->
            <div class="lg:col-span-7 flex flex-col gap-4">
                <!-- Card 1: Saluran Dukungan & Bantuan -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h3
                        class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faLifeRing" class="text-main" />
                        <span>Saluran Bantuan & Dukungan Pengguna</span>
                    </h3>

                    <div class="space-y-4">
                        <!-- Help Center URL -->
                        <div>
                            <TextField
                                id="help_center_url"
                                v-model="form.help_center_url"
                                label="Tautan Pusat Bantuan (Help Center)"
                                placeholder="cth. https://help.sollu.id"
                                :error="form.errors.help_center_url"
                            />
                            <p class="text-xs text-slate-400 mt-1">
                                Tautan panduan atau dokumentasi yang dibuka saat pengguna menekan
                                menu "Dokumentasi & Panduan" di popover bantuan.
                            </p>
                            <div
                                v-if="form.help_center_url"
                                class="mt-2 text-xs text-slate-500 flex items-center gap-1.5 bg-slate-50 border border-slate-100 p-2 rounded-lg"
                            >
                                <span class="font-medium text-slate-600">Pratinjau Tautan:</span>
                                <a
                                    :href="previewHelpCenterUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-main hover:underline flex items-center gap-1 break-all"
                                >
                                    <span>{{ previewHelpCenterUrl }}</span>
                                    <FontAwesomeIcon
                                        :icon="faArrowUpRightFromSquare"
                                        class="text-[10px]"
                                    />
                                </a>
                            </div>
                        </div>

                        <!-- WhatsApp Support Number -->
                        <div>
                            <TextField
                                id="whatsapp_support_number"
                                v-model="form.whatsapp_support_number"
                                label="Nomor WhatsApp Customer Service"
                                placeholder="cth. 081234567890 atau 6281234567890"
                                :error="form.errors.whatsapp_support_number"
                            />
                            <p class="text-xs text-slate-400 mt-1">
                                Nomor WhatsApp CS untuk bantuan langsung pengguna. Nomor berawalan 0
                                otomatis diubah menjadi kode negara 62 saat disimpan.
                            </p>
                            <div
                                v-if="form.whatsapp_support_number"
                                class="mt-2 text-xs text-slate-500 flex items-center gap-1.5 bg-slate-50 border border-slate-100 p-2 rounded-lg"
                            >
                                <span class="font-medium text-slate-600"
                                    >Pratinjau Chat WhatsApp:</span
                                >
                                <a
                                    :href="previewWhatsappUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-emerald-600 hover:underline flex items-center gap-1 break-all"
                                >
                                    <span>https://wa.me/{{ formattedWhatsappNumber }}</span>
                                    <FontAwesomeIcon
                                        :icon="faArrowUpRightFromSquare"
                                        class="text-[10px]"
                                    />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky Bottom Action Bar -->
                <div
                    class="flex justify-end sticky bottom-4 z-10 bg-white/90 backdrop-blur-xs p-3 rounded-xl border border-slate-200"
                >
                    <button
                        type="button"
                        class="btn btn-main flex items-center gap-2"
                        :disabled="form.processing"
                        @click="submitSettings"
                    >
                        <FontAwesomeIcon :icon="faSave" />
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}</span>
                    </button>
                </div>
            </div>

            <!-- Right Column: Feature Flags / Payment Integrations -->
            <div class="lg:col-span-5 flex flex-col gap-4">
                <!-- Card 2: Integrasi Pembayaran -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h3
                        class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faCreditCard" class="text-main" />
                        <span>Gerbang Pembayaran Platform</span>
                    </h3>

                    <div class="space-y-3">
                        <div
                            class="p-4 rounded-xl border transition-colors"
                            :class="
                                midtransEnabled
                                    ? 'border-emerald-200 bg-emerald-50/30'
                                    : 'border-slate-200 bg-slate-50/50'
                            "
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-semibold text-sm text-slate-800">
                                            Pembayaran Otomatis Midtrans
                                        </h4>
                                        <span
                                            class="px-2 py-0.5 text-[10px] rounded-full font-bold uppercase tracking-wider"
                                            :class="
                                                midtransEnabled
                                                    ? 'bg-emerald-100 text-emerald-700'
                                                    : 'bg-slate-200 text-slate-600'
                                            "
                                        >
                                            {{ midtransEnabled ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                        Aktifkan gerbang pembayaran otomatis Midtrans (Virtual
                                        Account, QRIS, e-Wallet) untuk tagihan langganan merchant
                                        secara global.
                                    </p>
                                </div>
                                <div class="mt-0.5 shrink-0">
                                    <Switch
                                        id="midtrans_payment_enabled"
                                        :model-value="midtransEnabled ? 1 : 0"
                                        @update:model-value="confirmToggleMidtrans"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-4 p-3 rounded-lg bg-blue-50/70 border border-blue-100 text-[11px] text-blue-800 leading-relaxed"
                    >
                        <strong>Informasi:</strong> Saat pembayaran otomatis aktif, merchant dapat
                        membayar invoice langganan menggunakan metode instan tanpa verifikasi
                        manual.
                    </div>
                </div>
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import { computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faSave,
    faLifeRing,
    faCreditCard,
    faArrowUpRightFromSquare,
} from '@fortawesome/free-solid-svg-icons'

import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import TextField from '@/Components/Form/TextField.vue'
import Switch from '@/Components/Form/Switch.vue'
import { useModalStore } from '@/store/notification'

const props = defineProps({
    midtransEnabled: {
        type: Boolean,
        default: false,
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
})

const modalStore = useModalStore()

const form = useForm({
    help_center_url: props.settings?.help_center_url || '',
    whatsapp_support_number: props.settings?.whatsapp_support_number || '',
})

const previewHelpCenterUrl = computed(() => {
    const url = (form.help_center_url || '').trim()
    if (!url) return ''
    if (
        !/^(?:f|ht)tps?:\/\//i.test(url) &&
        !url.startsWith('#') &&
        !url.startsWith('mailto:') &&
        !url.startsWith('tel:')
    ) {
        return `https://${url}`
    }
    return url
})

const formattedWhatsappNumber = computed(() => {
    let num = (form.whatsapp_support_number || '').replace(/[^0-9]/g, '')
    if (num.startsWith('0')) {
        num = '62' + num.slice(1)
    }
    return num
})

const previewWhatsappUrl = computed(() => {
    if (!formattedWhatsappNumber.value) return ''
    return `https://wa.me/${formattedWhatsappNumber.value}`
})

const submitSettings = () => {
    form.put(route('cockpit.config.settings.update'), {
        preserveScroll: true,
    })
}

const confirmToggleMidtrans = val => {
    const isActivating = Boolean(val)
    modalStore.confirm({
        title: isActivating ? 'Aktifkan Pembayaran Midtrans' : 'Nonaktifkan Pembayaran Midtrans',
        message: isActivating
            ? 'Apakah Anda yakin ingin mengaktifkan metode pembayaran otomatis Midtrans untuk seluruh tagihan langganan merchant?'
            : 'Apakah Anda yakin ingin menonaktifkan metode pembayaran otomatis Midtrans? Merchant hanya akan dapat melakukan pembayaran langganan melalui transfer rekening manual.',
        type: isActivating ? 'info' : 'warning',
        confirmText: isActivating ? 'Ya, Aktifkan' : 'Ya, Nonaktifkan',
        confirmClass: isActivating ? 'btn-main' : 'btn-danger',
        cancelText: 'Batal',
        onConfirm: () => {
            router.patch(
                route('cockpit.config.feature-flag.update'),
                {
                    feature_name: 'midtrans_payment_enabled',
                    enabled: isActivating ? 1 : 0,
                },
                {
                    preserveScroll: true,
                }
            )
        },
    })
}
</script>
