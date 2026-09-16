<template>
    <div v-if="loading" class="flex justify-center items-center h-48">
        <div class="animate-pulse flex flex-col items-center gap-2">
            <div
                class="w-8 h-8 border-4 border-main border-t-transparent rounded-full animate-spin"
            ></div>
            <span class="text-sm text-neutral-500">Memuat data paket...</span>
        </div>
    </div>

    <form v-else class="flex flex-col gap-3" @submit.prevent="submit">
        <!-- Informasi Dasar Paket -->
        <div class="bg-white border border-slate-200 rounded-lg p-3 flex flex-col gap-2.5">
            <h4
                class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-1.5 flex items-center justify-between"
            >
                <span>Informasi Dasar Paket</span>
                <span
                    v-if="isEdit"
                    class="text-xs font-mono font-semibold px-2 py-0.5 bg-slate-100 rounded text-slate-600"
                >
                    {{ planCode }}
                </span>
            </h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <TextField
                    v-if="!isEdit"
                    v-model="form.code"
                    label="Kode Paket (Unik)"
                    placeholder="cth. enterprise-retail"
                    :feedback="form.errors.code"
                />

                <TextField
                    v-model="form.name"
                    label="Nama Paket"
                    placeholder="cth. Enterprise Retail Plan"
                    :feedback="form.errors.name"
                    :class="{ 'sm:col-span-2': isEdit }"
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <NumberField
                    v-model="form.price_per_outlet"
                    label="Harga per Outlet / Bulan (Rp)"
                    placeholder="cth. 150000"
                    :feedback="form.errors.price_per_outlet"
                />

                <NumberField
                    v-model="form.yearly_discount_percent"
                    label="Diskon Tahunan (%)"
                    placeholder="cth. 20"
                    :feedback="form.errors.yearly_discount_percent"
                />
            </div>

            <!-- Toggle switches for status and public catalog -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                <div
                    class="flex items-center justify-between p-2.5 bg-slate-50 rounded-lg border border-slate-200"
                >
                    <div>
                        <div class="text-xs font-semibold text-slate-800">Status Aktif</div>
                        <div class="text-[11px] text-slate-500">Dapat dilanggan</div>
                    </div>
                    <Switch v-model="form.is_active" />
                </div>

                <div
                    class="flex items-center justify-between p-2.5 bg-slate-50 rounded-lg border border-slate-200"
                >
                    <div>
                        <div class="text-xs font-semibold text-slate-800">Katalog Publik</div>
                        <div class="text-[11px] text-slate-500">Tampil di billing</div>
                    </div>
                    <Switch v-model="form.is_public" />
                </div>
            </div>
        </div>

        <!-- Penugasan Khusus Merchant (Non-Katalog / Enterprise) -->
        <div class="bg-white border border-slate-200 rounded-lg p-3 flex flex-col gap-2.5">
            <div class="border-b border-slate-100 pb-1.5 flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                        <FontAwesomeIcon :icon="faBuilding" class="text-main" />
                        <span>Penugasan Khusus Merchant (Opsional)</span>
                    </h4>
                    <p class="text-xs text-slate-500">
                        Pilih merchant tertentu untuk paket non-katalog / custom B2B eksklusif
                    </p>
                </div>
                <span
                    v-if="selectedMerchant"
                    class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full"
                >
                    Terassign
                </span>
            </div>

            <!-- Merchant Already Selected Card -->
            <div
                v-if="selectedMerchant"
                class="flex items-center justify-between p-2.5 bg-purple-50/70 border border-purple-200 rounded-lg"
            >
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-9 h-9 rounded-lg bg-purple-200 text-purple-800 flex items-center justify-center font-bold text-sm shrink-0"
                    >
                        {{ (selectedMerchant.name || 'M').charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <span>{{ selectedMerchant.name }}</span>
                            <span
                                v-if="selectedMerchant.status"
                                class="text-[9px] px-1.5 py-0.2 rounded font-semibold uppercase tracking-wider"
                                :class="
                                    selectedMerchant.status === 'active'
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-slate-200 text-slate-600'
                                "
                            >
                                {{ selectedMerchant.status }}
                            </span>
                        </div>
                        <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                            <span v-if="selectedMerchant.owner_name"
                                >Pemilik: {{ selectedMerchant.owner_name }}</span
                            >
                            <span v-if="selectedMerchant.email"
                                >• {{ selectedMerchant.email }}</span
                            >
                        </div>
                    </div>
                </div>
                <button
                    type="button"
                    class="btn btn-outline-danger btn-xs text-[11px]"
                    title="Hapus penugasan merchant"
                    @click="clearMerchantAssignment"
                >
                    <FontAwesomeIcon :icon="faXmark" class="mr-1" />
                    Hapus
                </button>
            </div>

            <!-- Merchant Search Input -->
            <div v-else class="space-y-1">
                <AsyncSelectField
                    v-model="form.business_id"
                    label=""
                    placeholder="Ketik nama bisnis, nama pemilik, atau email merchant..."
                    :api-url="route('cockpit.merchants.search')"
                    search-param-name="query"
                    :min-chars="2"
                    :feedback="form.errors.business_id"
                    @select="onSelectMerchant"
                >
                    <template #option="{ item }">
                        <div class="flex items-center justify-between py-0.5">
                            <div>
                                <div class="text-xs font-bold text-slate-800">
                                    {{ item.name }}
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    {{ item.owner_name ? `Pemilik: ${item.owner_name}` : '' }}
                                    {{ item.email ? ` • ${item.email}` : '' }}
                                </div>
                            </div>
                            <span
                                v-if="item.status"
                                class="text-[10px] px-1.5 py-0.5 rounded font-semibold uppercase tracking-wider"
                                :class="
                                    item.status === 'active'
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-slate-100 text-slate-600'
                                "
                            >
                                {{ item.status }}
                            </span>
                        </div>
                    </template>
                </AsyncSelectField>
                <div class="text-[11px] text-slate-400">
                    Kosongkan jika paket ini diperuntukkan sebagai paket umum (bukan penugasan
                    khusus).
                </div>
            </div>
        </div>

        <!-- Poin Marketing / Tampilan Fitur di Brosur (Features List) -->
        <div class="bg-white border border-slate-200 rounded-lg p-3 flex flex-col gap-2">
            <div class="flex justify-between items-center border-b border-slate-100 pb-1.5">
                <div>
                    <h4 class="text-sm font-bold text-slate-800">Daftar Poin Brosur Pemasaran</h4>
                    <p class="text-xs text-slate-500">
                        Poin ringkas fitur yang tampil pada kartu brosur merchant
                    </p>
                </div>
                <button type="button" class="btn btn-outline-main btn-xs" @click="addFeature">
                    <FontAwesomeIcon :icon="faPlus" class="mr-1" />
                    Tambah Poin
                </button>
            </div>

            <div v-if="form.features && form.features.length" class="space-y-2">
                <div
                    v-for="(feature, index) in form.features"
                    :key="index"
                    class="flex items-start gap-2 p-2 bg-slate-50 border border-slate-200 rounded-lg relative"
                >
                    <div class="flex-1 space-y-2">
                        <TextField
                            v-model="feature.title"
                            placeholder="Judul Poin (cth. Multi Outlet)"
                            :feedback="form.errors[`features.${index}.title`]"
                        />
                        <TextField
                            v-model="feature.detail"
                            placeholder="Detail Poin (cth. Kelola banyak cabang dalam 1 sistem)"
                            :feedback="form.errors[`features.${index}.detail`]"
                        />
                    </div>
                    <button
                        type="button"
                        class="text-danger hover:text-danger/80 p-2 text-sm transition-colors"
                        title="Hapus Poin"
                        @click="removeFeature(index)"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                </div>
            </div>

            <div
                v-else
                class="text-center py-4 text-xs text-slate-400 border border-dashed border-slate-200 rounded-lg"
            >
                Belum ada poin brosur ditambahkan untuk paket ini.
            </div>
        </div>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-end gap-2 w-full">
                <button
                    type="button"
                    class="btn btn-outline-slate-400"
                    :disabled="form.processing"
                    @click="close"
                >
                    Batal
                </button>
                <button
                    type="submit"
                    class="btn btn-main"
                    :disabled="form.processing"
                    @click="submit"
                >
                    {{
                        form.processing
                            ? 'Menyimpan...'
                            : isEdit
                              ? 'Simpan Perubahan'
                              : 'Buat Paket'
                    }}
                </button>
            </div>
        </Teleport>
    </form>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import TextField from '@/Components/Form/TextField.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import Switch from '@/Components/Form/Switch.vue'
import AsyncSelectField from '@/Components/Form/AsyncSelectField.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPlus, faTrash, faBuilding, faXmark } from '@fortawesome/free-solid-svg-icons'
import { usePopUpStore } from '@/store/popup'
import axios from 'axios'

const props = defineProps({
    planId: {
        type: String,
        default: null,
    },
})

const popUpStore = usePopUpStore()
const isMounted = ref(false)
const loading = ref(Boolean(props.planId))
const isEdit = computed(() => Boolean(props.planId))
const planCode = ref('')
const selectedMerchant = ref(null)

const form = useForm({
    code: '',
    name: '',
    price_per_outlet: 0,
    yearly_discount_percent: 0,
    is_active: true,
    is_public: true,
    business_id: null,
    features: [],
})

onMounted(async () => {
    isMounted.value = true
    if (props.planId) {
        try {
            const response = await axios.get(route('cockpit.subscription-plans.show', props.planId))
            const data = response.data
            planCode.value = data.code
            form.code = data.code
            form.name = data.name
            form.price_per_outlet = Number(data.price_per_outlet) || 0
            form.yearly_discount_percent = Number(data.yearly_discount_percent) || 0
            form.is_active = Boolean(data.is_active)
            form.is_public = Boolean(data.is_public ?? true)
            form.business_id = data.business_id || null
            if (data.business) {
                selectedMerchant.value = data.business
            }
            form.features = Array.isArray(data.features) ? data.features.map(f => ({ ...f })) : []
        } catch (err) {
            console.error('Failed to load plan details:', err)
        } finally {
            loading.value = false
        }
    }
})

const onSelectMerchant = merchant => {
    if (merchant) {
        selectedMerchant.value = merchant
        form.business_id = merchant.id
        form.is_public = false
    }
}

const clearMerchantAssignment = () => {
    selectedMerchant.value = null
    form.business_id = null
}

const addFeature = () => {
    if (!form.features) {
        form.features = []
    }
    form.features.push({
        title: '',
        detail: '',
    })
}

const removeFeature = index => {
    form.features.splice(index, 1)
}

const close = () => {
    popUpStore.close()
}

const submit = () => {
    if (isEdit.value) {
        form.put(route('cockpit.subscription-plans.update', props.planId), {
            preserveScroll: true,
            onSuccess: () => {
                popUpStore.close()
            },
        })
    } else {
        form.post(route('cockpit.subscription-plans.store'), {
            preserveScroll: true,
            onSuccess: () => {
                popUpStore.close()
            },
        })
    }
}
</script>
