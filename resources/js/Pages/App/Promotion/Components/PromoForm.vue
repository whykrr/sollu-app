<template>
    <form class="space-y-3" @submit.prevent="submit">
        <!-- Section 1: Informasi Dasar -->
        <div class="space-y-2">
            <TextField
                id="name"
                v-model="form.name"
                label="Nama Promo"
                placeholder="Misal: Diskon Gajian 10% atau Promo Opening"
                :error="form.errors.name"
                required
            />
            <TextareaField
                id="description"
                v-model="form.description"
                label="Deskripsi (Opsional)"
                placeholder="Penjelasan ringkas syarat dan ketentuan promo"
                :error="form.errors.description"
                rows="2"
            />
        </div>

        <!-- Section 2: Tipe & Target Diskon -->
        <div class="space-y-2 border-t border-slate-100 pt-3">
            <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider">Skema Diskon</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <DropdownField
                    id="target_type"
                    v-model="form.target_type"
                    label="Target Diskon"
                    :options="targetTypeOptions"
                    :error="form.errors.target_type"
                />
                <DropdownField
                    id="promo_type"
                    v-model="form.promo_type"
                    label="Tipe Diskon"
                    :options="promoTypeOptions"
                    :error="form.errors.promo_type"
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <NumberField
                    id="discount_value"
                    v-model="form.discount_value"
                    :label="form.promo_type === 'percentage' ? 'Nilai Diskon (%)' : 'Nilai Diskon (Rp)'"
                    :error="form.errors.discount_value"
                    required
                />
                <NumberField
                    v-if="form.promo_type === 'percentage'"
                    id="max_discount"
                    v-model="form.max_discount"
                    label="Batas Maksimum Diskon (Rp)"
                    placeholder="Misal: 50000"
                    :error="form.errors.max_discount"
                />
            </div>
        </div>

        <!-- Section 3: Cakupan Produk (Kondisional) -->
        <div v-if="form.target_type === 'product'" class="space-y-2 border-t border-slate-100 pt-3">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-0">Cakupan Produk</h4>
                <button
                    v-if="selectedProducts.length > 0"
                    type="button"
                    class="text-xs text-main hover:underline font-medium cursor-pointer"
                    @click="selectedProducts = []"
                >
                    Hapus Semua
                </button>
            </div>
            <AsyncSelectField
                id="product_search"
                label="Cari & Pilih Produk"
                :api-url="route('api.internal.inventory-items.search')"
                placeholder="Ketik nama produk..."
                :error="form.errors.inventory_item_ids"
                @select="addProduct"
            />

            <div v-if="selectedProducts.length > 0" class="mt-2 space-y-1 max-h-48 overflow-y-auto">
                <div
                    v-for="product in selectedProducts"
                    :key="product.id"
                    class="flex items-center justify-between bg-slate-50 p-2 rounded-lg border border-slate-200 text-xs"
                >
                    <span class="font-medium text-slate-800">{{ product.name }}</span>
                    <button
                        type="button"
                        class="text-danger hover:text-rose-700 p-1"
                        @click="removeProduct(product.id)"
                    >
                        <FontAwesomeIcon :icon="faTimes" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Section 4: Jadwal & Waktu -->
        <div class="space-y-2 border-t border-slate-100 pt-3">
            <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider">
                Periode & Jam Operasional
            </h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <TextField
                    id="start_date"
                    v-model="form.start_date"
                    type="date"
                    label="Tanggal Mulai"
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
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <TextField
                    id="start_time"
                    v-model="form.start_time"
                    type="time"
                    label="Jam Mulai (Opsional)"
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
        </div>

        <!-- Section 5: Cakupan Outlet -->
        <div
            v-if="!selectedOutlet && outlets.length > 1"
            class="space-y-2 border-t border-slate-100 pt-3"
        >
            <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider">
                Cakupan Outlet
            </h4>
            <Switch
                v-model="form.applies_to_all_outlets"
                label="Berlaku di Semua Outlet"
                description="Promo dapat digunakan oleh seluruh gerai yang kamu miliki."
            />

            <div v-if="!form.applies_to_all_outlets" class="mt-2 space-y-2">
                <div class="bg-slate-50 border border-slate-200 p-3 rounded-xl space-y-2">
                    <SelectionGroupField
                        v-model="form.outlet_ids"
                        multiple
                        label="Pilih Outlet Tertentu"
                        :options="outlets"
                        name="outlet_ids"
                        class="sm btn-sm"
                    />
                </div>
                <div v-if="form.errors.outlet_ids" class="text-danger text-xs">
                    {{ form.errors.outlet_ids }}
                </div>
            </div>
        </div>

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
    promo: {
        type: Object,
        default: null,
    },
})

const isMounted = ref(false)
const { outlets: userOutlets, selectedOutlet } = useAuth()
const { getOptions } = useEnum()

const outlets = computed(
    () =>
        userOutlets.value?.map(store => ({
            value: store.id,
            label: store.name,
        })) || []
)

const targetTypeOptions = computed(() => getOptions('PromoTarget'))
const promoTypeOptions = computed(() => getOptions('PromoType'))

const selectedProducts = ref(props.promo?.inventory_items || [])

const form = useForm({
    name: props.promo?.name || '',
    description: props.promo?.description || '',
    target_type: props.promo?.target_type || 'bill',
    promo_type: props.promo?.promo_type || 'percentage',
    discount_value: props.promo?.discount_value || 0,
    max_discount: props.promo?.max_discount || null,
    start_date: props.promo?.start_date || '',
    end_date: props.promo?.end_date || '',
    start_time: props.promo?.start_time || '',
    end_time: props.promo?.end_time || '',
    applies_to_all_outlets: props.promo
        ? props.promo.applies_to_all_outlets
        : !selectedOutlet.value,
    inventory_item_ids: [],
    outlet_ids:
        props.promo?.outlets?.map(o => o.id) ||
        (selectedOutlet.value ? [selectedOutlet.value.id] : []),
})

const { handleCancel, forceClose } = useFormDirtyGuard({ form })

onMounted(async () => {
    isMounted.value = true
    if (props.promo?.id && (!props.promo.outlets || !props.promo.inventory_items)) {
        try {
            const response = await axios.get(route('promotions.show', props.promo.id))
            const data = response.data
            if (data.outlets && data.outlets.length > 0) {
                form.outlet_ids = data.outlets.map(o => o.id)
            }
            if (data.inventory_items && data.inventory_items.length > 0) {
                selectedProducts.value = data.inventory_items.map(i => ({
                    id: i.id,
                    name: i.name,
                }))
            }
        } catch (error) {
            console.error('Gagal memuat detail relasi promo:', error)
        }
    }
})

// Logic to clear max_discount when type is fixed
watch(
    () => form.promo_type,
    newVal => {
        if (newVal === 'fixed') {
            form.max_discount = null
        }
    }
)

// Update inventory_item_ids form array when selectedProducts changes
watch(
    selectedProducts,
    newVal => {
        form.inventory_item_ids = newVal.map(p => p.id)
    },
    { deep: true, immediate: true }
)

const addProduct = product => {
    if (!selectedProducts.value.find(p => p.id === product.id)) {
        selectedProducts.value.push(product)
    }
}

const removeProduct = id => {
    selectedProducts.value = selectedProducts.value.filter(p => p.id !== id)
}

const submit = () => {
    if (props.promo?.id) {
        form.put(route('promotions.update', props.promo.id), {
            onSuccess: () => forceClose(),
        })
    } else {
        form.post(route('promotions.store'), {
            onSuccess: () => forceClose(),
        })
    }
}
</script>
