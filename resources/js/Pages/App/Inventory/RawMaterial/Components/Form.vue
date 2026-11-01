<template>
    <div>
        <form class="space-y-2" @submit.prevent="submit">
            <TextField
                id="name"
                v-model="form.name"
                label="Nama Bahan Baku"
                :error="form.errors.name"
                required
            />

            <DropdownField
                id="uom_id"
                v-model="form.uom_id"
                label="Satuan (UOM)"
                :options="uomOptions"
                :error="form.errors.uom_id"
                required
            />

            <label
                class="flex items-center justify-between border border-slate-200 p-3 rounded-xl cursor-pointer hover:bg-slate-50 transition w-full"
            >
                <div>
                    <div class="font-bold text-sm text-slate-800">Lacak Inventori (Stok)</div>
                    <div class="text-xs text-slate-500">
                        Lacak stok masuk dan keluar untuk bahan baku ini.
                    </div>
                </div>
                <input
                    v-model="form.track_inventory"
                    type="checkbox"
                    class="rounded h-5 w-5 text-primary cursor-pointer"
                />
            </label>

            <DisclosureSection
                title="Kode & Parameter Stok"
                :closed-hint="form.sku || form.barcode ? 'Ada kode' : 'Opsional'"
                :default-open="Boolean(form.sku || form.barcode || form.minimum_stock)"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                    <TextField id="sku" v-model="form.sku" label="SKU" :error="form.errors.sku" />

                    <TextField
                        id="barcode"
                        v-model="form.barcode"
                        label="Barcode"
                        :error="form.errors.barcode"
                    />
                </div>

                <TextField
                    v-if="form.track_inventory"
                    id="minimum_stock"
                    v-model="form.minimum_stock"
                    type="number"
                    label="Minimum Stok"
                    :error="form.errors.minimum_stock"
                />
            </DisclosureSection>
        </form>

        <!-- Footer Actions Di-teleportkan ke PopUpPage Modal Footer -->
        <Teleport v-if="isMounted" to="#popUpFooter">
            <button
                v-if="rawMaterial?.id"
                type="button"
                class="btn btn-flat text-danger mr-auto cursor-pointer"
                title="Hapus data bahan baku"
                aria-label="Hapus"
                :disabled="form.processing"
                @click="confirmDelete"
            >
                Hapus
            </button>
            <button
                type="button"
                class="btn btn-flat cursor-pointer"
                title="Batal dan tutup formulir"
                aria-label="Batal"
                :disabled="form.processing"
                @click="handleCancel"
            >
                Batal
            </button>
            <button
                type="button"
                class="btn btn-main cursor-pointer"
                title="Simpan data bahan baku"
                aria-label="Simpan"
                :disabled="form.processing || !form.isDirty"
                @click="submit"
            >
                <span v-if="form.processing">Menyimpan...</span>
                <span v-else>Simpan</span>
            </button>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, watch, ref, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard'
import { useModalStore } from '@/store/notification'
import DisclosureSection from '@/Components/Form/DisclosureSection.vue'
import TextField from '@/Components/Form/TextField.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'

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

const modalStore = useModalStore()
const isMounted = ref(false)

onMounted(() => {
    isMounted.value = true
})

const confirmDelete = () => {
    if (!props.rawMaterial?.id) return
    modalStore.openModalDelete(route('inventory.raw-materials.destroy', props.rawMaterial.id))
}

const form = useForm({
    name: '',
    sku: '',
    barcode: '',
    uom_id: '',
    track_inventory: true,
    minimum_stock: 0,
})

const { handleCancel, forceClose } = useFormDirtyGuard({ form })

const uomOptions = computed(() => {
    return props.uoms.map(uom => ({
        label: uom.name,
        value: uom.id,
    }))
})

watch(
    () => props.rawMaterial,
    data => {
        form.reset()
        if (data) {
            form.name = data.name || ''
            form.sku = data.sku || ''
            form.barcode = data.barcode || ''
            form.uom_id = data.uom_id || ''
            form.track_inventory = data.track_inventory ?? true
            form.minimum_stock = data.minimum_stock || 0
        }
        form.defaults()
    },
    { immediate: true }
)

const submit = () => {
    if (props.rawMaterial?.id) {
        form.put(route('inventory.raw-materials.update', props.rawMaterial.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => forceClose(),
        })
    } else {
        form.post(route('inventory.raw-materials.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => forceClose(),
        })
    }
}
</script>
