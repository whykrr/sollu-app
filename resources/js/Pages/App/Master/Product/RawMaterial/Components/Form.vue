<template>
    <div>
        <form class="space-y-3" @submit.prevent="submit">
            <TextField
                v-model="form.name"
                label="Nama Bahan Baku"
                placeholder="Misal: Susu Segar UHT, Biji Kopi Arabika"
                :error="form.errors.name"
                required
            />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <TextField
                    v-model="form.sku"
                    label="SKU Bahan Baku (Opsional)"
                    placeholder="Misal: BB-SUSU-01"
                    :error="form.errors.sku"
                />

                <TextField
                    v-model="form.barcode"
                    label="Barcode (Opsional)"
                    placeholder="Scan atau input barcode"
                    :error="form.errors.barcode"
                />
            </div>

            <DropdownField
                v-model="form.uom_id"
                label="Satuan (UOM)"
                :options="formattedUoms"
                placeholder="-- Pilih Satuan --"
                :error="form.errors.uom_id"
                required
            />

            <div class="border border-slate-200 p-2.5 rounded-xl space-y-2 bg-slate-50/50">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-semibold text-xs text-slate-800">
                            Lacak Inventori (Stok)
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Aktifkan untuk memantau mutasi saldo dan peringatan stok menipis
                        </div>
                    </div>
                    <Switch v-model="form.track_inventory" size="sm" />
                </div>

                <div v-if="form.track_inventory" class="pt-2 border-t border-slate-200">
                    <NumberField
                        v-model="form.minimum_stock"
                        label="Batas Minimum Stok (Peringatan)"
                        placeholder="0"
                        :error="form.errors.minimum_stock"
                        min="0"
                        step="0.01"
                    />
                </div>
            </div>
        </form>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-end gap-2 w-full">
                <button type="button" class="btn btn-flat btn-sm" @click="closeModal">Batal</button>
                <button
                    type="button"
                    :disabled="form.processing"
                    class="btn btn-main btn-sm"
                    @click="submit"
                >
                    <FontAwesomeIcon :icon="faSave" class="mr-1" />
                    {{
                        form.processing
                            ? 'Menyimpan...'
                            : isEdit
                              ? 'Simpan Perubahan'
                              : 'Simpan Bahan Baku'
                    }}
                </button>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSave } from '@fortawesome/free-solid-svg-icons'
import { usePopUpStore } from '@/store/popup'
import TextField from '@/Components/Form/TextField.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import Switch from '@/Components/Form/Switch.vue'

const props = defineProps({
    rawMaterial: {
        type: Object,
        default: null,
    },
    uoms: {
        type: Array,
        default: () => [],
    },
})

const popUpStore = usePopUpStore()
const isMounted = ref(false)
const isEdit = computed(() => Boolean(props.rawMaterial && props.rawMaterial.id))

const form = useForm({
    name: props.rawMaterial?.name || props.rawMaterial?.product_item?.name || '',
    sku: props.rawMaterial?.sku || props.rawMaterial?.product_item?.sku || '',
    barcode: props.rawMaterial?.barcode || props.rawMaterial?.product_item?.barcode || '',
    uom_id: props.rawMaterial?.uom_id || '',
    track_inventory: props.rawMaterial ? Boolean(props.rawMaterial.track_inventory) : true,
    minimum_stock: props.rawMaterial?.minimum_stock ?? 0,
})

const formattedUoms = computed(() => {
    return (props.uoms || []).map(u => ({
        value: u.id,
        label: u.name,
    }))
})

onMounted(() => {
    isMounted.value = true
})

const closeModal = () => {
    popUpStore.close()
}

const submit = () => {
    if (isEdit.value) {
        form.put(route('master.raw-materials.update', props.rawMaterial.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => closeModal(),
        })
    } else {
        form.post(route('master.raw-materials.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => closeModal(),
        })
    }
}
</script>
