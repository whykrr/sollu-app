<template>
    <div class="space-y-4 pb-2">
        <!-- 1. Foto Layanan -->
        <div>
            <label
                class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5"
            >
                Foto Layanan
            </label>
            <ProductImagesUploader v-model="form.images" :error="form.errors.images" />
        </div>

        <!-- 2. Core Fields (Nama, Kategori, Tarif Dasar) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="col-span-1 sm:col-span-2">
                <TextField
                    v-model="form.name"
                    label="Nama Layanan"
                    placeholder="Misal: Potong Rambut Pria / Facial Treatment"
                    :error="form.errors.name"
                    required
                />
            </div>

            <div class="col-span-1">
                <SearchableDropdownField
                    v-model="form.product_category_id"
                    :options="categoryOptions"
                    label="Kategori Layanan"
                    placeholder="Pilih Kategori"
                    search-placeholder="Cari kategori..."
                    :error="form.errors.product_category_id"
                    clearable
                />
            </div>

            <div class="col-span-1">
                <NumberField
                    v-model="form.base_price"
                    label="Tarif Dasar Layanan"
                    placeholder="0"
                    prefix="Rp"
                    :min="0"
                    :error="form.errors.base_price"
                    required
                />
            </div>
        </div>

        <!-- 3. Progressive Disclosure (Pengaturan Lanjutan) -->
        <DisclosureSection
            title="Pengaturan Lanjutan"
            description="Kode layanan, deskripsi, penyesuaian tarif per outlet & visibilitas kasir"
            :error="hasAdvancedError"
        >
            <div class="space-y-3 pt-1">
                <!-- Kode Layanan / SKU -->
                <div>
                    <TextField
                        v-model="form.code"
                        label="Kode Layanan (Opsional)"
                        placeholder="Misal: SRV-HAIR-01"
                        :error="form.errors.code"
                    />
                </div>

                <!-- Deskripsi Layanan -->
                <div>
                    <TextareaField
                        v-model="form.description"
                        label="Deskripsi & Rincian Layanan"
                        placeholder="Tuliskan keterangan detail pengerjaan layanan..."
                        rows="2"
                        :error="form.errors.description"
                    />
                </div>

                <!-- Switch Kustomisasi Tarif per Outlet -->
                <div v-if="outlets.length > 1" class="space-y-2">
                    <div
                        class="flex items-center justify-between border border-slate-200 p-2.5 rounded-xl hover:bg-slate-50 transition"
                    >
                        <div>
                            <div class="font-semibold text-xs text-slate-800">
                                Kustomisasi Tarif per Outlet
                            </div>
                            <div class="text-[11px] text-slate-500">
                                Tetapkan tarif yang berbeda untuk masing-masing cabang
                            </div>
                        </div>
                        <Switch v-model="customizeOutletPrices" size="sm" />
                    </div>

                    <!-- Outlet Price Inputs -->
                    <div
                        v-if="customizeOutletPrices"
                        class="space-y-2 pl-2 border-l-2 border-primary-200 bg-slate-50/50 p-2.5 rounded-r-xl"
                    >
                        <div
                            v-for="outlet in outlets"
                            :key="outlet.id"
                            class="flex items-center justify-between gap-3 text-xs"
                        >
                            <span class="font-medium text-slate-700 truncate w-1/2">
                                {{ outlet.name }}
                            </span>
                            <div class="w-1/2">
                                <NumberField
                                    v-model="outletPriceMap[outlet.id]"
                                    placeholder="Ikuti Tarif Dasar"
                                    prefix="Rp"
                                    :min="0"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ketersediaan Outlet -->
                <div v-if="outlets.length > 0" class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-700">
                        Ketersediaan Layanan di Outlet
                    </label>
                    <div class="bg-slate-50 border border-slate-200 p-2 rounded-lg">
                        <SelectionGroupField
                            v-model="selectedOutlets"
                            multiple
                            :options="formattedOutlets"
                            name="outlet_ids"
                            class="sm btn-sm"
                            show-select-all
                        />
                    </div>
                </div>

                <!-- Switch Visibilitas Kasir -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                    <div
                        class="flex items-center justify-between border border-slate-200 p-2.5 rounded-xl hover:bg-slate-50 transition"
                    >
                        <div>
                            <div class="font-semibold text-xs text-slate-800">
                                Tampilkan di Kasir
                            </div>
                            <div class="text-[11px] text-slate-500">
                                Muncul di katalog transaksi kasir
                            </div>
                        </div>
                        <Switch v-model="form.is_show" size="sm" />
                    </div>

                    <div
                        class="flex items-center justify-between border border-slate-200 p-2.5 rounded-xl hover:bg-slate-50 transition"
                    >
                        <div>
                            <div class="font-semibold text-xs text-slate-800">Dapat Dijual</div>
                            <div class="text-[11px] text-slate-500">
                                Aktifkan transaksi untuk layanan ini
                            </div>
                        </div>
                        <Switch v-model="form.sellable" size="sm" />
                    </div>
                </div>
            </div>
        </DisclosureSection>
    </div>

    <!-- Sticky Footer Actions -->
    <Teleport v-if="isMounted" to="#popUpFooter">
        <div class="flex items-center justify-end w-full gap-2">
            <button type="button" class="btn btn-flat btn-sm" @click="handleCancel">Batal</button>
            <button
                type="button"
                class="btn btn-main btn-sm"
                :disabled="form.processing"
                @click="submit"
            >
                <FontAwesomeIcon :icon="faSave" class="mr-1" />
                <span>{{ isEdit ? 'Simpan Perubahan' : 'Simpan Layanan' }}</span>
            </button>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSave } from '@fortawesome/free-solid-svg-icons'
import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard'
import TextField from '@/Components/Form/TextField.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import TextareaField from '@/Components/Form/TextareaField.vue'
import SearchableDropdownField from '@/Components/Form/SearchableDropdownField.vue'
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue'
import Switch from '@/Components/Form/Switch.vue'
import DisclosureSection from '@/Components/Form/DisclosureSection.vue'
import ProductImagesUploader from '@/Pages/App/Master/Product/Components/ProductImagesUploader.vue'

const props = defineProps({
    editMode: { type: Boolean, default: false },
    service: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    outlets: { type: Array, default: () => [] },
})

const isEdit = computed(() => props.editMode)
const isMounted = ref(false)

onMounted(() => {
    isMounted.value = true
})

const form = useForm({
    id: props.service?.id || null,
    name: props.service?.name || '',
    code: props.service?.code || '',
    product_category_id: props.service?.product_category_id || '',
    description: props.service?.description || '',
    product_type: 'service',
    is_show: props.service?.is_show ?? true,
    sellable: props.service?.sellable ?? true,
    base_price: '',
    outlet_prices: [],
    outlets: [],
    images: props.service?.images || [],
})

const customizeOutletPrices = ref(false)
const outletPriceMap = ref({})
const outletStatusMap = ref({})

// Initialize Outlets & Prices
props.outlets.forEach(o => {
    outletStatusMap.value[o.id] = true
    outletPriceMap.value[o.id] = ''
})

if (isEdit.value && props.service) {
    if (props.service.prices) {
        const bp = props.service.prices.find(p => !p.outlet_id)
        if (bp) form.base_price = String(bp.amount)

        const customPrices = props.service.prices.filter(p => p.outlet_id)
        if (customPrices.length > 0) {
            customizeOutletPrices.value = true
            customPrices.forEach(p => {
                outletPriceMap.value[p.outlet_id] = String(p.amount)
            })
        }
    }

    if (props.service.outlets && props.service.outlets.length > 0) {
        props.outlets.forEach(o => {
            const pivot = props.service.outlets.find(item => item.id === o.id)
            outletStatusMap.value[o.id] = pivot ? Boolean(pivot.pivot?.is_enabled ?? true) : false
        })
    }
}

const { handleCancel, forceClose } = useFormDirtyGuard({ form })

const categoryOptions = computed(() => {
    return props.categories.map(c => ({
        label: c.label || c.name || '',
        value: c.value !== undefined && c.value !== null ? c.value : c.id || '',
    }))
})

const formattedOutlets = computed(() => {
    return props.outlets.map(o => ({
        value: o.id,
        label: o.name,
    }))
})

const selectedOutlets = computed({
    get: () => {
        return Object.keys(outletStatusMap.value)
            .filter(id => outletStatusMap.value[id])
            .map(id => Number(id) || id)
    },
    set: newVal => {
        props.outlets.forEach(o => {
            outletStatusMap.value[o.id] = false
        })
        newVal.forEach(id => {
            outletStatusMap.value[id] = true
        })
    },
})

const hasAdvancedError = computed(() => {
    return Boolean(
        form.errors.code ||
        form.errors.description ||
        form.errors.is_show ||
        form.errors.sellable ||
        form.errors.outlets ||
        form.errors.outlet_prices
    )
})

const submit = () => {
    if (customizeOutletPrices.value) {
        form.outlet_prices = Object.keys(outletPriceMap.value)
            .filter(
                oId =>
                    outletPriceMap.value[oId] !== undefined &&
                    outletPriceMap.value[oId] !== null &&
                    outletPriceMap.value[oId] !== ''
            )
            .map(oId => ({
                outlet_id: oId,
                amount: Number(outletPriceMap.value[oId]),
            }))
    } else {
        form.outlet_prices = []
    }

    form.outlets = Object.keys(outletStatusMap.value).map(k => ({
        outlet_id: k,
        is_enabled: outletStatusMap.value[k],
        is_available: outletStatusMap.value[k],
    }))

    if (isEdit.value && props.service?.id) {
        form.transform(data => ({ ...data, _method: 'PUT' })).post(
            route('master.services.update', props.service.id),
            {
                onSuccess: () => forceClose(),
            }
        )
    } else {
        form.post(route('master.services.store'), {
            onSuccess: () => forceClose(),
        })
    }
}
</script>
