<template>
    <div class="space-y-3.5 pb-16">
        <!-- Bagian 1: Core Inputs (80/20 Wajib): Foto Layanan, Nama, Kategori, Tarif Layanan -->
        <div id="section-basic" class="space-y-3">
            <!-- 1. Foto Layanan -->
            <div>
                <label
                    class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5"
                >
                    Foto Layanan
                </label>
                <ProductImagesUploader v-model="form.images" :error="form.errors.images" />
            </div>

            <!-- 2. Core Fields (Nama, Kategori, Tarif Layanan) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
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
                        label="Tarif Layanan"
                        placeholder="0"
                        prefix="Rp"
                        :min="0"
                        :error="form.errors.base_price"
                        required
                    />
                </div>
            </div>
        </div>

        <!-- Bagian 2: Progressive Disclosure (Pengaturan Lanjutan, Default Collapsed) -->
        <DisclosureSection
            id="section-advanced"
            v-model="isAdvancedOpen"
            title="Pengaturan Lanjutan"
            description="Kode layanan, deskripsi pengerjaan, ketersediaan outlet & visibilitas kasir"
            :error="hasAdvancedError"
        >
            <div class="space-y-2.5 pt-1">
                <!-- Kode Layanan -->
                <div>
                    <TextField
                        v-model="form.code"
                        label="Kode Layanan"
                        placeholder="Misal: SRV-HAIR-01 (Opsional)"
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

                <!-- Switch Visibilitas: Dapat Dijual terlebih dahulu, lalu Tampilkan di Kasir -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
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

                    <div
                        class="flex items-center justify-between border border-slate-200 p-2.5 rounded-xl transition"
                        :class="
                            !form.sellable
                                ? 'bg-slate-50 opacity-60 cursor-not-allowed'
                                : 'hover:bg-slate-50'
                        "
                    >
                        <div class="pr-2">
                            <div class="font-semibold text-xs text-slate-800">
                                Tampilkan di Kasir
                            </div>
                            <div class="text-[11px] text-slate-500">
                                <span v-if="!form.sellable" class="text-amber-600 font-medium">
                                    Layanan yang tidak dapat dijual otomatis disembunyikan dari
                                    katalog kasir.
                                </span>
                                <span v-else> Muncul di katalog transaksi kasir </span>
                            </div>
                        </div>
                        <Switch v-model="form.is_show" size="sm" :disabled="!form.sellable" />
                    </div>
                </div>

                <!-- Ketersediaan Outlet (Multi-Outlet) -->
                <div
                    v-if="outlets.length > 1 && !selectedOutlet"
                    class="border border-slate-200 p-2.5 rounded-xl space-y-1.5"
                >
                    <div class="font-semibold text-xs text-slate-800">
                        Ketersediaan Layanan di Outlet
                    </div>
                    <div class="text-[11px] text-slate-500">
                        Pilih cabang / outlet yang menyediakan layanan ini:
                    </div>
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
            </div>
        </DisclosureSection>

        <!-- Bagian 3: Progressive Disclosure Card Matriks Tarif Cabang -->
        <ProductDisclosureCard
            v-if="outlets.length > 1"
            id="card-pricing"
            title="Matriks Tarif Cabang"
            description="Kustomisasi nominal tarif layanan khusus per cabang outlet"
            :icon="faTag"
            :feature="$enums.FeatureEnum?.PRODUCT_PRICE_MATRIX || 'product_price_matrix'"
            :switch-value="customizeOutletPrices"
            :is-open="isPricingOpen"
            :badge="customizeOutletPrices ? 'Kustom Aktif' : null"
            :error="hasPricingError"
            @update:switch-value="customizeOutletPrices = $event"
            @update:is-open="isPricingOpen = $event"
            @switch-change="handlePricingSwitchChange"
        >
            <div class="space-y-2">
                <p class="text-[11px] text-slate-500">
                    Tentukan tarif khusus untuk cabang tertentu. Jika dikosongkan, cabang tersebut
                    akan menggunakan Tarif Layanan (Rp {{ form.base_price || 0 }}).
                </p>

                <div v-if="activeOutletsCount > 0" class="space-y-2">
                    <div
                        v-for="outlet in outlets"
                        v-show="outletStatusMap[outlet.id]"
                        :key="outlet.id"
                        class="flex flex-col sm:flex-row sm:items-center gap-2 bg-slate-50/70 p-2.5 rounded-lg border border-slate-200"
                    >
                        <div class="sm:w-1/3 text-xs font-semibold text-slate-700 truncate">
                            {{ outlet.name }}
                        </div>
                        <div class="sm:w-2/3">
                            <NumberField
                                v-model="outletPriceMap[outlet.id]"
                                placeholder="Ikuti Tarif Layanan"
                                prefix="Rp"
                                :min="0"
                                size="sm"
                                :error="getOutletPriceError(outlet.id)"
                            />
                        </div>
                    </div>
                </div>
                <div v-else class="text-xs text-slate-400 italic py-2 text-center">
                    Tidak ada cabang outlet yang aktif untuk layanan ini.
                </div>
            </div>
        </ProductDisclosureCard>
    </div>

    <!-- Sticky Footer Actions Teleport #popUpFooter -->
    <Teleport v-if="isMounted" to="#popUpFooter">
        <div class="flex items-center justify-between w-full gap-2">
            <button
                type="button"
                class="btn btn-flat btn-sm"
                :disabled="form.processing"
                @click="handleCancel()"
            >
                Batal
            </button>
            <button
                type="button"
                class="btn btn-main btn-sm min-w-[120px]"
                :disabled="form.processing"
                @click="submit"
            >
                <FontAwesomeIcon
                    :icon="form.processing ? faSpinner : faSave"
                    :class="{ 'animate-spin': form.processing }"
                    class="mr-1"
                />
                <span>{{ isEdit ? 'Simpan Perubahan' : 'Simpan Layanan' }}</span>
            </button>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSave, faSpinner, faTag } from '@fortawesome/free-solid-svg-icons'
import { useAuth } from '@/Composable/useAuth'
import { usePlanFeature } from '@/Composable/usePlanFeature'
import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard'
import TextField from '@/Components/Form/TextField.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import TextareaField from '@/Components/Form/TextareaField.vue'
import SearchableDropdownField from '@/Components/Form/SearchableDropdownField.vue'
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue'
import Switch from '@/Components/Form/Switch.vue'
import DisclosureSection from '@/Components/Form/DisclosureSection.vue'
import ProductImagesUploader from '@/Pages/App/Master/Product/Components/ProductImagesUploader.vue'
import ProductDisclosureCard from '@/Pages/App/Master/Product/Components/ProductDisclosureCard.vue'

defineEmits(['close'])

const props = defineProps({
    editMode: { type: Boolean, default: false },
    service: { type: Object, default: null },
    targetStepId: { type: String, default: null },
    categories: { type: Array, default: () => [] },
    outlets: { type: Array, default: () => [] },
})

const { selectedOutlet } = useAuth()
const { hasFeature } = usePlanFeature()

const isPricingLocked = computed(() => {
    return !hasFeature('product_price_matrix')
})

const isEdit = computed(() => props.editMode)
const isMounted = ref(false)
const isAdvancedOpen = ref(false)
const isPricingOpen = ref(false)

const getInitialBasePrice = () => {
    if (props.service?.prices) {
        const bp = props.service.prices.find(p => !p.outlet_id)
        if (bp && bp.amount !== undefined && bp.amount !== null) {
            return String(bp.amount)
        }
    }
    return ''
}

const form = useForm({
    id: props.service?.id || null,
    name: props.service?.name || '',
    code: props.service?.code || '',
    product_category_id: props.service?.product_category_id || '',
    description: props.service?.description || '',
    product_type: 'service',
    is_show: props.service?.is_show ?? true,
    sellable: props.service?.sellable ?? true,
    base_price: getInitialBasePrice(),
    outlet_prices: [],
    outlets: [],
    images: props.service?.images || [],
})

const customizeOutletPrices = ref(false)
const outletPriceMap = ref({})
const outletStatusMap = ref({})

// Initialize Outlets & Prices
props.outlets.forEach(o => {
    outletPriceMap.value[o.id] = ''
})

if (isEdit.value && props.service) {
    if (props.service.prices) {
        const customPrices = props.service.prices.filter(p => p.outlet_id)
        if (customPrices.length > 0 && !isPricingLocked.value) {
            customizeOutletPrices.value = true
            isPricingOpen.value = true
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
    } else {
        props.outlets.forEach(o => {
            outletStatusMap.value[o.id] = true
        })
    }
} else {
    // Create Mode
    if (selectedOutlet.value) {
        props.outlets.forEach(o => {
            outletStatusMap.value[o.id] = o.id === selectedOutlet.value.id
        })
    } else {
        props.outlets.forEach(o => {
            outletStatusMap.value[o.id] = true
        })
    }
}

// Auto-enable customizeOutletPrices when user enters a custom price if feature is not locked
watch(
    outletPriceMap,
    newMap => {
        if (!customizeOutletPrices.value && !isPricingLocked.value) {
            const hasAnyVal = Object.values(newMap).some(
                val => val !== '' && val !== null && val !== undefined
            )
            if (hasAnyVal) {
                customizeOutletPrices.value = true
            }
        }
    },
    { deep: true }
)

const activeOutletsCount = computed(() => {
    return props.outlets.filter(o => outletStatusMap.value[o.id]).length
})

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
        return Object.keys(outletStatusMap.value).filter(id => outletStatusMap.value[id])
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

const hasBasicError = computed(() => {
    return Boolean(
        form.errors.name ||
        form.errors.base_price ||
        form.errors.product_category_id ||
        form.errors.images ||
        Object.keys(form.errors).some(k => k.startsWith('images.'))
    )
})

const hasAdvancedError = computed(() => {
    return Boolean(
        form.errors.code ||
        form.errors.description ||
        form.errors.is_show ||
        form.errors.sellable ||
        form.errors.outlets ||
        Object.keys(form.errors).some(k => k.startsWith('outlets.'))
    )
})

const hasPricingError = computed(() => {
    return Boolean(
        form.errors.outlet_prices ||
        Object.keys(form.errors).some(k => k.startsWith('outlet_prices'))
    )
})

const getOutletPriceError = outletId => {
    if (!form.errors) return null
    for (const key of Object.keys(form.errors)) {
        if (key.startsWith('outlet_prices.')) {
            const match = key.match(/^outlet_prices\.(\d+)(?:\.|$)/)
            if (match) {
                const index = Number(match[1])
                if (form.outlet_prices?.[index]?.outlet_id === outletId) {
                    return form.errors[key]
                }
            }
        }
    }
    return null
}

// Causality: sellable & is_show
watch(
    () => form.sellable,
    isSellable => {
        if (!isSellable) {
            form.is_show = false
        }
    }
)

watch(
    () => form.is_show,
    isShow => {
        if (isShow && !form.sellable) {
            form.is_show = false
        }
    }
)

const scrollToElement = id => {
    const el = document.getElementById(id)
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
}

// Auto-expand on 422 server validation errors & scroll to topmost error section
watch(
    () => form.errors,
    newErrors => {
        if (!newErrors || Object.keys(newErrors).length === 0) return

        let firstTarget = null

        if (hasBasicError.value) {
            if (!firstTarget) firstTarget = 'section-basic'
        }

        if (hasAdvancedError.value) {
            isAdvancedOpen.value = true
            if (!firstTarget) firstTarget = 'section-advanced'
        }

        if (hasPricingError.value) {
            isPricingOpen.value = true
            if (!firstTarget) firstTarget = 'card-pricing'
        }

        if (firstTarget) {
            nextTick(() => scrollToElement(firstTarget))
        }
    },
    { deep: true }
)

const handlePricingSwitchChange = val => {
    customizeOutletPrices.value = val
    if (val && !isPricingOpen.value) {
        isPricingOpen.value = true
    }
}

const applyTargetStep = targetId => {
    if (!targetId || targetId === 'basic') return

    if (targetId === 'pricing') {
        isPricingOpen.value = true
        nextTick(() => scrollToElement('card-pricing'))
    } else if (targetId === 'advanced') {
        isAdvancedOpen.value = true
        nextTick(() => scrollToElement('section-advanced'))
    }
}

watch(
    () => props.targetStepId,
    newId => {
        if (newId) {
            applyTargetStep(newId)
        }
    }
)

onMounted(() => {
    isMounted.value = true

    if (props.targetStepId) {
        setTimeout(() => {
            applyTargetStep(props.targetStepId)
        }, 150)
    }
})

const submit = () => {
    if (customizeOutletPrices.value && !isPricingLocked.value) {
        form.outlet_prices = Object.keys(outletPriceMap.value)
            .filter(
                oId =>
                    outletStatusMap.value[oId] &&
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
