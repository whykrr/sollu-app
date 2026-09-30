<template>
    <form class="space-y-4" @submit.prevent="submit">
        <!-- TIER 1: Informasi Dasar & Trigger -->
        <div class="space-y-2.5">
            <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                1. Informasi Dasar & Pemicu
            </h4>

            <TextField
                id="name"
                v-model="form.name"
                label="Nama Promo"
                placeholder="Misal: Diskon Gajian 20% atau Voucher Akhir Pekan"
                :error="form.errors.name"
                required
            />

            <TextareaField
                id="description"
                v-model="form.description"
                label="Deskripsi (Opsional)"
                placeholder="Penjelasan ringkas syarat dan ketentuan promo untuk kasir atau pelanggan"
                :error="form.errors.description"
                rows="2"
            />

            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-slate-700">
                    Mode Aplikasi Promo <span class="text-danger">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button
                        type="button"
                        class="p-2.5 rounded-lg border text-left transition-all cursor-pointer flex flex-col gap-0.5"
                        :class="
                            form.application_mode === 'automatic'
                                ? 'border-main bg-main/5 text-slate-900 font-medium ring-1 ring-main'
                                : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-600'
                        "
                        @click="form.application_mode = 'automatic'"
                    >
                        <span class="text-xs font-semibold text-slate-800">Otomatis di Kasir</span>
                        <span class="text-[11px] text-slate-500">
                            Langsung aktif saat keranjang belanja memenuhi syarat
                        </span>
                    </button>

                    <button
                        type="button"
                        class="p-2.5 rounded-lg border text-left transition-all cursor-pointer flex flex-col gap-0.5"
                        :class="
                            form.application_mode === 'manual'
                                ? 'border-main bg-main/5 text-slate-900 font-medium ring-1 ring-main'
                                : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-600'
                        "
                        @click="form.application_mode = 'manual'"
                    >
                        <span class="text-xs font-semibold text-slate-800"
                            >Kode Promo (Manual)</span
                        >
                        <span class="text-[11px] text-slate-500">
                            Memerlukan input kode kupon voucher sebelum potongan aktif
                        </span>
                    </button>
                </div>
                <div v-if="form.errors.application_mode" class="text-danger text-xs mt-1">
                    {{ form.errors.application_mode }}
                </div>
            </div>

            <!-- Input Kode Promo jika mode Manual -->
            <div
                v-if="form.application_mode === 'manual'"
                class="bg-amber-50/60 border border-amber-200 p-3 rounded-xl space-y-1 transition-all"
            >
                <TextField
                    id="promo_code"
                    v-model="form.promo_code"
                    label="Kode Kupon Promo"
                    placeholder="Misal: GAJIANHEMAT / DISC50"
                    :error="form.errors.promo_code"
                    class="font-mono uppercase font-bold"
                    required
                />
                <span class="text-[11px] text-slate-500 block">
                    Gunakan kombinasi huruf kapital, angka, strip, atau garis bawah tanpa spasi.
                </span>
            </div>
        </div>

        <!-- TIER 2: Conditions (Syarat & Kriteria Target) -->
        <div class="space-y-3 border-t border-slate-100 pt-3">
            <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                2. Syarat & Kriteria Target
            </h4>

            <DropdownField
                id="target_scope"
                v-model="form.target_scope"
                label="Cakupan Target Promo"
                :options="targetScopeOptions"
                :error="form.errors.target_scope"
                required
            />

            <!-- Dynamic Multi-Picker: Kategori -->
            <div
                v-if="form.target_scope === 'category'"
                class="bg-slate-50 border border-slate-200 p-3 rounded-xl space-y-2"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-700">Pilih Kategori Produk</span>
                    <button
                        v-if="selectedCategories.length > 0"
                        type="button"
                        class="text-xs text-danger hover:underline cursor-pointer"
                        @click="selectedCategories = []"
                    >
                        Hapus Semua
                    </button>
                </div>

                <AsyncSelectField
                    id="category_picker"
                    label="Cari Kategori"
                    :api-url="route('api.internal.categories.search')"
                    placeholder="Ketik nama kategori..."
                    :error="form.errors.category_ids"
                    size="sm"
                    reset-on-select
                    @select="addCategory"
                />

                <div v-if="selectedCategories.length > 0" class="flex flex-wrap gap-1.5 pt-1">
                    <span
                        v-for="cat in selectedCategories"
                        :key="cat.id"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-slate-300 rounded-lg text-xs font-medium text-slate-800"
                    >
                        {{ cat.name }}
                        <button
                            type="button"
                            class="text-slate-400 hover:text-danger cursor-pointer ml-1"
                            @click="removeCategory(cat.id)"
                        >
                            <FontAwesomeIcon :icon="faTimes" />
                        </button>
                    </span>
                </div>
            </div>

            <!-- Dynamic Multi-Picker: Produk Master -->
            <div
                v-if="form.target_scope === 'product'"
                class="bg-slate-50 border border-slate-200 p-3 rounded-xl space-y-2"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-700">Pilih Produk Spesifik</span>
                    <button
                        v-if="selectedProducts.length > 0"
                        type="button"
                        class="text-xs text-danger hover:underline cursor-pointer"
                        @click="selectedProducts = []"
                    >
                        Hapus Semua
                    </button>
                </div>

                <AsyncSelectField
                    id="product_picker"
                    label="Cari Produk"
                    :api-url="route('api.internal.products.search')"
                    placeholder="Ketik nama produk..."
                    :error="form.errors.product_ids"
                    size="sm"
                    reset-on-select
                    @select="addProduct"
                />

                <div v-if="selectedProducts.length > 0" class="flex flex-wrap gap-1.5 pt-1">
                    <span
                        v-for="prod in selectedProducts"
                        :key="prod.id"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-slate-300 rounded-lg text-xs font-medium text-slate-800"
                    >
                        {{ prod.name }}
                        <button
                            type="button"
                            class="text-slate-400 hover:text-danger cursor-pointer ml-1"
                            @click="removeProduct(prod.id)"
                        >
                            <FontAwesomeIcon :icon="faTimes" />
                        </button>
                    </span>
                </div>
            </div>

            <!-- Dynamic Multi-Picker: Varian SKU -->
            <div
                v-if="form.target_scope === 'variant'"
                class="bg-slate-50 border border-slate-200 p-3 rounded-xl space-y-2"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-700"
                        >Pilih Varian Item (SKU)</span
                    >
                    <button
                        v-if="selectedProductItems.length > 0"
                        type="button"
                        class="text-xs text-danger hover:underline cursor-pointer"
                        @click="selectedProductItems = []"
                    >
                        Hapus Semua
                    </button>
                </div>

                <AsyncSelectField
                    id="variant_picker"
                    label="Cari Varian Item"
                    :api-url="route('api.internal.inventory-items.search')"
                    placeholder="Ketik nama atau SKU varian..."
                    :error="form.errors.product_item_ids"
                    size="sm"
                    reset-on-select
                    @select="addProductItem"
                />

                <div v-if="selectedProductItems.length > 0" class="flex flex-wrap gap-1.5 pt-1">
                    <span
                        v-for="item in selectedProductItems"
                        :key="item.id"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-slate-300 rounded-lg text-xs font-medium text-slate-800"
                    >
                        {{ item.name }}
                        <span v-if="item.sku" class="text-[10px] text-slate-400 font-mono"
                            >({{ item.sku }})</span
                        >
                        <button
                            type="button"
                            class="text-slate-400 hover:text-danger cursor-pointer ml-1"
                            @click="removeProductItem(item.id)"
                        >
                            <FontAwesomeIcon :icon="faTimes" />
                        </button>
                    </span>
                </div>
            </div>

            <!-- Syarat Minimal Belanja & Qty -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <NumberField
                    id="min_subtotal"
                    v-model="form.min_subtotal"
                    label="Min. Subtotal Belanja (Rp)"
                    placeholder="Misal: 50000 (0 = Tanpa Minimum)"
                    :error="form.errors.min_subtotal"
                />

                <NumberField
                    id="min_quantity"
                    v-model="form.min_quantity"
                    label="Min. Kuantitas Barang (Qty)"
                    placeholder="Misal: 3 (Untuk diskon grosir)"
                    :error="form.errors.min_quantity"
                />
            </div>

            <!-- Periode Tanggal -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <TextField
                    id="start_date"
                    v-model="form.start_date"
                    type="date"
                    label="Tanggal Mulai Berlaku"
                    :error="form.errors.start_date"
                    required
                />

                <TextField
                    id="end_date"
                    v-model="form.end_date"
                    type="date"
                    label="Tanggal Berakhir"
                    :error="form.errors.end_date"
                    required
                />
            </div>

            <!-- Jam Operasional (Happy Hour) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <TextField
                    id="start_time"
                    v-model="form.start_time"
                    type="time"
                    label="Jam Mulai (Opsional Happy Hour)"
                    :error="form.errors.start_time"
                />

                <TextField
                    id="end_time"
                    v-model="form.end_time"
                    type="time"
                    label="Jam Selesai (Opsional)"
                    :error="form.errors.end_time"
                />
            </div>

            <!-- Hari Berlaku (Day Chips) -->
            <div class="space-y-1.5">
                <label class="block text-xs font-medium text-slate-700">
                    Hari Berlaku (Opsional - Kosongkan jika berlaku setiap hari)
                </label>
                <div class="flex flex-wrap gap-1">
                    <button
                        v-for="day in dayOptions"
                        :key="day.id"
                        type="button"
                        class="px-2.5 py-1 rounded-md text-xs font-medium border transition-colors cursor-pointer"
                        :class="
                            form.days_of_week.includes(day.id)
                                ? 'bg-main text-white border-main font-semibold'
                                : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'
                        "
                        @click="toggleDay(day.id)"
                    >
                        {{ day.label }}
                    </button>
                </div>
            </div>

            <!-- Cakupan Outlet -->
            <div class="space-y-2 border-t border-slate-100 pt-3">
                <div class="space-y-0.5">
                    <label class="block text-xs font-semibold text-slate-700">
                        Cakupan Outlet Berlaku <span class="text-danger">*</span>
                    </label>
                    <p class="text-[11px] text-slate-500">
                        Tentukan apakah promo berlaku di seluruh cabang atau hanya cabang tertentu.
                    </p>
                </div>

                <div
                    class="rounded-lg border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition-colors p-3.5 flex items-start justify-between gap-4"
                >
                    <div class="flex-1">
                        <h4 class="text-xs font-semibold text-slate-800">
                            Berlaku di Semua Outlet
                        </h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Promo dapat dinikmati oleh pelanggan di seluruh cabang tokomu
                        </p>
                    </div>

                    <div class="mt-0.5 shrink-0">
                        <Switch id="applies_to_all_outlets" v-model="form.applies_to_all_outlets" />
                    </div>
                </div>

                <div v-if="!form.applies_to_all_outlets" class="space-y-2 pt-1">
                    <div class="bg-slate-50 border border-slate-200 p-3 rounded-xl space-y-2">
                        <SelectionGroupField
                            v-model="form.outlet_ids"
                            multiple
                            show-select-all
                            label="Pilih Cabang / Outlet yang Berlaku"
                            :options="outletOptions"
                            name="outlet_ids"
                            :feedback="form.errors.outlet_ids"
                            class="sm btn-sm"
                        />
                    </div>
                    <div v-if="form.errors.outlet_ids" class="text-danger text-xs">
                        {{ form.errors.outlet_ids }}
                    </div>
                </div>
            </div>
        </div>

        <!-- TIER 3: Benefit (Imbalan Potongan Diskon) -->
        <div class="space-y-3 border-t border-slate-100 pt-3">
            <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                3. Imbalan Potongan Diskon
            </h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <DropdownField
                    id="discount_type"
                    v-model="form.discount_type"
                    label="Tipe Diskon"
                    :options="discountTypeOptions"
                    :error="form.errors.discount_type"
                    required
                />

                <NumberField
                    id="discount_value"
                    v-model="form.discount_value"
                    :label="
                        form.discount_type === 'percentage'
                            ? 'Nilai Diskon (% Persentase)'
                            : 'Nominal Potongan (Rp Tetap)'
                    "
                    :placeholder="
                        form.discount_type === 'percentage' ? 'Misal: 15' : 'Misal: 10000'
                    "
                    :error="form.errors.discount_value"
                    required
                />
            </div>

            <!-- Batas Maksimal Diskon jika Persentase -->
            <div v-if="form.discount_type === 'percentage'" class="space-y-1">
                <NumberField
                    id="max_discount_amount"
                    v-model="form.max_discount_amount"
                    label="Batas Maksimal Potongan (Rp) - Opsional"
                    placeholder="Misal: 25000 (Kosongkan jika tanpa batas maksimum)"
                    :error="form.errors.max_discount_amount"
                />
                <span class="text-[11px] text-slate-500 block">
                    Mencegah nilai potongan persentase membengkak pada transaksi bernilai besar.
                </span>
            </div>
        </div>

        <!-- Sticky Footer Teleport -->
        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-end gap-2 w-full">
                <button type="button" class="btn btn-flat" @click="handleCancel()">Batal</button>
                <button
                    type="submit"
                    class="btn btn-highlight-main"
                    :disabled="form.processing"
                    @click="submit"
                >
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Promo' }}
                </button>
            </div>
        </Teleport>
    </form>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faTimes } from '@fortawesome/free-solid-svg-icons'
import TextField from '@/Components/Form/TextField.vue'
import TextareaField from '@/Components/Form/TextareaField.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue'
import AsyncSelectField from '@/Components/Form/AsyncSelectField.vue'
import Switch from '@/Components/Form/Switch.vue'
import { useAuth } from '@/Composable/useAuth'
import { useEnum } from '@/Composable/useEnum'
import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard'

const props = defineProps({
    promotion: {
        type: Object,
        default: null,
    },
    promo: {
        type: Object,
        default: null,
    },
    outlets: {
        type: Array,
        default: () => [],
    },
})

const activePromo = computed(() => props.promotion || props.promo || null)

const isMounted = ref(false)
const { outlets: userOutlets, selectedOutlet } = useAuth()
const { getOptions } = useEnum()

const availableOutlets = computed(() => {
    if (props.outlets && props.outlets.length > 0) {
        return props.outlets
    }
    return userOutlets.value || []
})

const outletOptions = computed(() =>
    availableOutlets.value.map(store => ({
        value: store.id,
        label: store.name,
    }))
)

const targetScopeOptions = computed(() => getOptions('PromotionTargetScope'))
const discountTypeOptions = computed(() => getOptions('PromotionDiscountType'))

const dayOptions = [
    { id: 1, label: 'Senin' },
    { id: 2, label: 'Selasa' },
    { id: 3, label: 'Rabu' },
    { id: 4, label: 'Kamis' },
    { id: 5, label: 'Jumat' },
    { id: 6, label: 'Sabtu' },
    { id: 7, label: 'Minggu' },
]

const selectedCategories = ref([])
const selectedProducts = ref([])
const selectedProductItems = ref([])

const initialTargetScope =
    activePromo.value?.target_scope || activePromo.value?.target_type || 'transaction'

const initialDiscountType =
    activePromo.value?.discount_type || activePromo.value?.promo_type || 'percentage'

const getInitialSelectedOutlets = () => {
    if (activePromo.value?.outlets && activePromo.value.outlets.length > 0) {
        return activePromo.value.outlets.map(o => o.id)
    }
    if (selectedOutlet.value) {
        return [selectedOutlet.value.id]
    }
    return availableOutlets.value.map(o => o.id)
}

const form = useForm({
    name: activePromo.value?.name || '',
    description: activePromo.value?.description || '',
    application_mode: activePromo.value?.application_mode || 'automatic',
    promo_code: activePromo.value?.promo_code || '',
    target_scope: initialTargetScope,
    discount_type: initialDiscountType,
    discount_value: activePromo.value?.discount_value ?? 0,
    max_discount_amount:
        activePromo.value?.max_discount_amount ?? activePromo.value?.max_discount ?? null,
    min_subtotal: activePromo.value?.min_subtotal ?? null,
    min_quantity: activePromo.value?.min_quantity ?? null,
    start_date: activePromo.value?.start_date || '',
    end_date: activePromo.value?.end_date || '',
    start_time: activePromo.value?.start_time || '',
    end_time: activePromo.value?.end_time || '',
    days_of_week: Array.isArray(activePromo.value?.days_of_week)
        ? [...activePromo.value.days_of_week]
        : [],
    applies_to_all_outlets:
        activePromo.value !== null ? Boolean(activePromo.value.applies_to_all_outlets) : true,
    outlet_ids: getInitialSelectedOutlets(),
    category_ids: [],
    product_ids: [],
    product_item_ids: [],
})

const { handleCancel, forceClose } = useFormDirtyGuard({ form })

onMounted(async () => {
    isMounted.value = true

    if (activePromo.value?.id) {
        try {
            const response = await axios.get(route('promotions.show', activePromo.value.id))
            const data = response.data

            if (data.applies_to_all_outlets !== undefined) {
                form.applies_to_all_outlets = Boolean(data.applies_to_all_outlets)
            }
            if (data.outlets && data.outlets.length > 0) {
                form.outlet_ids = data.outlets.map(o => o.id)
            }
            if (data.categories && data.categories.length > 0) {
                selectedCategories.value = data.categories.map(c => ({ id: c.id, name: c.name }))
            }
            if (data.products && data.products.length > 0) {
                selectedProducts.value = data.products.map(p => ({ id: p.id, name: p.name }))
            }
            if (data.product_items && data.product_items.length > 0) {
                selectedProductItems.value = data.product_items.map(i => ({
                    id: i.id,
                    name: i.name,
                    sku: i.sku,
                }))
            }
        } catch (error) {
            console.error('Gagal memuat relasi promo:', error)
        }
    }
})

// Synchronize reactive selection arrays into form submission arrays
watch(
    selectedCategories,
    newVal => {
        form.category_ids = newVal.map(c => c.id)
    },
    { deep: true, immediate: true }
)

watch(
    selectedProducts,
    newVal => {
        form.product_ids = newVal.map(p => p.id)
    },
    { deep: true, immediate: true }
)

watch(
    selectedProductItems,
    newVal => {
        form.product_item_ids = newVal.map(i => i.id)
    },
    { deep: true, immediate: true }
)

// Clear promo_code when application_mode is automatic
watch(
    () => form.application_mode,
    newVal => {
        if (newVal === 'automatic') {
            form.promo_code = ''
        }
    }
)

// Clear unselected target scope items when target_scope changes
watch(
    () => form.target_scope,
    newScope => {
        if (newScope !== 'category') {
            selectedCategories.value = []
            form.category_ids = []
        }
        if (newScope !== 'product') {
            selectedProducts.value = []
            form.product_ids = []
        }
        if (newScope !== 'variant') {
            selectedProductItems.value = []
            form.product_item_ids = []
        }
    }
)

// Clear max_discount_amount when discount_type is fixed
watch(
    () => form.discount_type,
    newVal => {
        if (newVal === 'fixed') {
            form.max_discount_amount = null
        }
    }
)

// Auto-fill outlet_ids when toggling off applies_to_all_outlets if empty
watch(
    () => form.applies_to_all_outlets,
    newVal => {
        if (!newVal && (!form.outlet_ids || form.outlet_ids.length === 0)) {
            form.outlet_ids = getInitialSelectedOutlets()
        }
    }
)

const toggleDay = dayId => {
    const index = form.days_of_week.indexOf(dayId)
    if (index > -1) {
        form.days_of_week.splice(index, 1)
    } else {
        form.days_of_week.push(dayId)
    }
}

const addCategory = cat => {
    if (!selectedCategories.value.find(c => c.id === cat.id)) {
        selectedCategories.value.push(cat)
    }
}

const removeCategory = id => {
    selectedCategories.value = selectedCategories.value.filter(c => c.id !== id)
}

const addProduct = prod => {
    if (!selectedProducts.value.find(p => p.id === prod.id)) {
        selectedProducts.value.push(prod)
    }
}

const removeProduct = id => {
    selectedProducts.value = selectedProducts.value.filter(p => p.id !== id)
}

const addProductItem = item => {
    const targetId = item.product_item_id || item.id
    if (!selectedProductItems.value.find(i => i.id === targetId)) {
        selectedProductItems.value.push({
            id: targetId,
            name: item.name,
            sku: item.sku,
        })
    }
}

const removeProductItem = id => {
    selectedProductItems.value = selectedProductItems.value.filter(i => i.id !== id)
}

const submit = () => {
    if (activePromo.value?.id) {
        form.put(route('promotions.update', activePromo.value.id), {
            onSuccess: () => forceClose(),
        })
    } else {
        form.post(route('promotions.store'), {
            onSuccess: () => forceClose(),
        })
    }
}
</script>
