<template>
    <MainPage>
        <template #header>
            <div
                class="flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-neutral-200/60 mb-4"
            >
                <div>
                    <h1 class="text-xl font-bold text-neutral-800">Platform Configuration</h1>
                    <div class="text-sm text-neutral-500">
                        Manage global settings, feature flags, and maintenance modes
                    </div>
                </div>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="btn btn-main btn-sm flex items-center gap-1.5"
                        :disabled="form.processing"
                        @click="submitSettings"
                    >
                        <FontAwesomeIcon :icon="faSave" />
                        <span v-if="form.processing">Menyimpan...</span>
                        <span v-else>Save Changes</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- System Settings -->
            <div class="bg-white rounded-xl shadow-sm border border-neutral-200/60 p-4">
                <h3 class="font-bold text-neutral-800 mb-4 border-b border-neutral-100 pb-2">
                    System Settings
                </h3>
                <div class="flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium text-neutral-800">Maintenance Mode</div>
                            <div class="text-xs text-neutral-500">
                                Enable this to block all merchant access during updates
                            </div>
                        </div>
                        <Switch id="maintenance_mode" />
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium text-neutral-800">Signups Allowed</div>
                            <div class="text-xs text-neutral-500">
                                Allow new merchants to register on the platform
                            </div>
                        </div>
                        <Switch id="signups_allowed" :model-value="1" />
                    </div>

                    <div class="pt-3 border-t border-neutral-100 flex flex-col gap-2">
                        <div>
                            <div class="font-medium text-neutral-800 flex items-center gap-1.5">
                                <FontAwesomeIcon
                                    :icon="faCircleQuestion"
                                    class="text-indigo-600 text-sm"
                                />
                                <span>Link Pusat Bantuan (Help Center)</span>
                            </div>
                            <div class="text-xs text-neutral-500 mt-0.5">
                                Tautan tujuan saat pengguna menekan tombol "Dokumentasi & Panduan"
                                di popover bantuan.
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-start gap-2">
                            <div class="flex-1">
                                <TextField
                                    id="help_center_url"
                                    v-model="form.help_center_url"
                                    type="text"
                                    placeholder="cth. https://help.sollu.id"
                                    :error="form.errors.help_center_url"
                                />
                            </div>
                            <button
                                type="button"
                                class="btn btn-main btn-sm h-[38px] px-3 shrink-0 flex items-center justify-center gap-1.5"
                                :disabled="form.processing"
                                @click="submitSettings"
                            >
                                <FontAwesomeIcon :icon="faSave" />
                                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</span>
                            </button>
                        </div>
                        <div
                            v-if="form.help_center_url"
                            class="text-xs text-neutral-400 flex items-center gap-1"
                        >
                            <span>Tautan aktif:</span>
                            <a
                                :href="form.help_center_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-indigo-600 hover:underline flex items-center gap-1 break-all"
                            >
                                <span>{{ form.help_center_url }}</span>
                                <FontAwesomeIcon :icon="faArrowUpFromBracket" class="text-[10px]" />
                            </a>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-neutral-100 flex flex-col gap-2">
                        <div>
                            <div class="font-medium text-neutral-800 flex items-center gap-1.5">
                                <FontAwesomeIcon
                                    :icon="faWhatsapp"
                                    class="text-emerald-600 text-sm"
                                />
                                <span>Nomor WhatsApp Dukungan CS</span>
                            </div>
                            <div class="text-xs text-neutral-500 mt-0.5">
                                Nomor WhatsApp CS untuk bantuan langsung pengguna (cth. 081234567890
                                atau 6281234567890).
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-start gap-2">
                            <div class="flex-1">
                                <TextField
                                    id="whatsapp_support_number"
                                    v-model="form.whatsapp_support_number"
                                    type="text"
                                    placeholder="cth. 081234567890"
                                    :error="form.errors.whatsapp_support_number"
                                />
                            </div>
                            <button
                                type="button"
                                class="btn btn-main btn-sm h-[38px] px-3 shrink-0 flex items-center justify-center gap-1.5"
                                :disabled="form.processing"
                                @click="submitSettings"
                            >
                                <FontAwesomeIcon :icon="faSave" />
                                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</span>
                            </button>
                        </div>
                        <div
                            v-if="form.whatsapp_support_number"
                            class="text-xs text-neutral-400 flex items-center gap-1"
                        >
                            <span>Tautan chat aktif:</span>
                            <a
                                :href="`https://wa.me/${form.whatsapp_support_number}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-emerald-600 hover:underline flex items-center gap-1 break-all"
                            >
                                <span>https://wa.me/{{ form.whatsapp_support_number }}</span>
                                <FontAwesomeIcon :icon="faArrowUpFromBracket" class="text-[10px]" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature Flags -->
            <div class="bg-white rounded-xl shadow-sm border border-neutral-200/60 p-4">
                <h3 class="font-bold text-neutral-800 mb-4 border-b border-neutral-100 pb-2">
                    Global Feature Flags
                </h3>
                <div class="flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium text-neutral-800">
                                Pembayaran Otomatis (Midtrans)
                            </div>
                            <div class="text-xs text-neutral-500">
                                Aktifkan/nonaktifkan metode pembayaran otomatis (via Midtrans)
                                secara global
                            </div>
                        </div>
                        <Switch
                            id="midtrans_enabled"
                            :model-value="midtransEnabled ? 1 : 0"
                            @update:model-value="toggleMidtrans"
                        />
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium text-neutral-800">AI Analytics Dashboard</div>
                            <div class="text-xs text-neutral-500">
                                Enable new AI insights on merchant dashboard
                            </div>
                        </div>
                        <Switch id="ai_analytics" />
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium text-neutral-800">Multi-Outlet Sync V2</div>
                            <div class="text-xs text-neutral-500">
                                Enable the new optimized sync engine for multi-outlets
                            </div>
                        </div>
                        <Switch id="multi_outlet" :model-value="1" />
                    </div>
                </div>
            </div>

            <!-- Subscription Pricing -->
            <div
                class="bg-white rounded-xl shadow-sm border border-neutral-200/60 lg:col-span-2 overflow-hidden flex flex-col p-4"
            >
                <div
                    class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3"
                >
                    <div>
                        <h3 class="font-bold text-neutral-800">Pengaturan Paket Langganan</h3>
                        <p class="text-xs text-neutral-500 mt-0.5">
                            Kelola harga paket langganan per outlet, fitur, kuota outlet, dan status
                            aktif/nonaktif.
                        </p>
                    </div>
                    <Link
                        :href="route('cockpit.subscription-plans.index')"
                        class="btn btn-outline-main btn-sm inline-flex items-center gap-1.5"
                    >
                        <FontAwesomeIcon :icon="faLayerGroup" />
                        Buka Pengaturan Langganan
                    </Link>
                </div>
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import MainPage from '@/Components/UI/MainPage.vue'
import Switch from '@/Components/Form/Switch.vue'
import TextField from '@/Components/Form/TextField.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faSave,
    faLayerGroup,
    faCircleQuestion,
    faArrowUpFromBracket,
} from '@fortawesome/free-solid-svg-icons'
import { faWhatsapp } from '@fortawesome/free-brands-svg-icons'
import { Link, router, useForm } from '@inertiajs/vue3'

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

const form = useForm({
    help_center_url: props.settings?.help_center_url || '',
    whatsapp_support_number: props.settings?.whatsapp_support_number || '',
})

const submitSettings = () => {
    form.put(route('cockpit.config.settings.update'), {
        preserveScroll: true,
    })
}

const toggleMidtrans = val => {
    router.patch(
        route('cockpit.config.feature-flag.update'),
        {
            feature_name: 'midtrans_payment_enabled',
            enabled: val ? 1 : 0,
        },
        {
            preserveScroll: true,
        }
    )
}
</script>
