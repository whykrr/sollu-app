<template>
    <div>
        <form class="space-y-3" @submit.prevent="submit">
            <div
                class="grid grid-cols-1 md:grid-cols-2 gap-2"
                :class="{ '!grid-cols-1': !(userOutlets.length > 1 && !selectedOutlet) }"
            >
                <!-- Pilihan Outlet Asal: HANYA jika userOutlets > 1 DAN tidak ada selectedOutlet aktif di sidebar -->
                <div v-if="userOutlets.length > 1 && !selectedOutlet">
                    <SearchableDropdownField
                        id="from_outlet_id"
                        v-model="form.from_outlet_id"
                        label="Dari Outlet (Asal)"
                        placeholder="Pilih Outlet Asal..."
                        search-placeholder="Cari outlet..."
                        :options="fromOutletOptions"
                        :error="form.errors.from_outlet_id"
                        :disabled="form.items.length > 0 && form.from_outlet_id !== ''"
                        required
                    />
                </div>

                <!-- Pilihan Outlet Tujuan -->
                <div>
                    <SearchableDropdownField
                        id="to_outlet_id"
                        v-model="form.to_outlet_id"
                        label="Ke Outlet (Tujuan)"
                        placeholder="Pilih Outlet Tujuan..."
                        search-placeholder="Cari outlet..."
                        :options="toOutletOptions"
                        :error="form.errors.to_outlet_id"
                        :disabled="!form.from_outlet_id"
                        required
                    />
                </div>
            </div>

            <TextareaField
                id="notes"
                v-model="form.notes"
                label="Catatan Mutasi (Opsional)"
                placeholder="Tambahkan catatan mengenai mutasi/perpindahan barang ini..."
                :class="{ 'is-invalid': form.errors.notes }"
                :error="form.errors.notes"
                rows="2"
            />

            <div class="border-t border-slate-200 pt-3 space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Daftar Barang Mutasi</h3>
                        <p class="text-xs text-slate-500">
                            Pilih dan tentukan kuantitas barang yang ingin dipindahkan ke outlet
                            tujuan.
                        </p>
                    </div>
                    <div>
                        <button
                            type="button"
                            class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                            :disabled="!form.from_outlet_id"
                            :title="
                                !form.from_outlet_id
                                    ? 'Pilih outlet asal terlebih dahulu'
                                    : 'Buka daftar barang untuk mutasi'
                            "
                            @click="showItemPicker = true"
                        >
                            <FontAwesomeIcon :icon="faPlus" />
                            <span>Item</span>
                        </button>
                    </div>
                </div>

                <div v-if="form.errors.items" class="text-danger text-xs mb-1">
                    {{ form.errors.items }}
                </div>

                <!-- Petunjuk Jika Outlet Belum Terpilih -->
                <div
                    v-if="!form.from_outlet_id"
                    class="text-center py-6 text-xs text-slate-500 border border-dashed border-slate-300 rounded-lg bg-slate-50/50"
                >
                    Pilih outlet asal terlebih dahulu untuk mencari barang inventori.
                </div>

                <!-- Empty State Jika Belum Ada Item -->
                <div
                    v-else-if="form.items.length === 0"
                    class="text-center py-6 text-xs text-slate-500 border border-dashed border-slate-300 rounded-lg bg-slate-50/50 space-y-2"
                >
                    <p>Belum ada barang ditambahkan untuk mutasi stok.</p>
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                        :disabled="!form.from_outlet_id"
                        @click="showItemPicker = true"
                    >
                        <FontAwesomeIcon :icon="faPlus" />
                        <span>Tambah Barang</span>
                    </button>
                </div>

                <!-- Daftar Item Mutasi -->
                <div v-else class="space-y-2 max-h-96 overflow-y-auto pr-1">
                    <div
                        v-for="(item, index) in form.items"
                        :key="item.inventory_item_id || index"
                        class="p-3 border border-slate-200 rounded-lg bg-white space-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-xs font-bold text-slate-400 w-5 text-center">
                                    {{ index + 1 }}.
                                </span>
                                <div class="min-w-0">
                                    <div class="font-bold text-xs text-slate-800 truncate">
                                        {{ item.name }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 truncate">
                                        SKU: {{ item.sku || '-' }} | Satuan:
                                        <span class="font-medium text-slate-600">{{
                                            item.uom || '-'
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="btn btn-flat btn-sm text-danger h-7 w-7 !p-0 inline-flex items-center justify-center cursor-pointer"
                                title="Hapus item"
                                @click="removeItem(index)"
                            >
                                <FontAwesomeIcon :icon="faTrash" />
                            </button>
                        </div>

                        <!-- Baris Input Kuantitas Transfer -->
                        <div
                            class="grid grid-cols-12 gap-2 pt-1 border-t border-slate-100 items-start"
                        >
                            <div class="col-span-12 sm:col-span-6">
                                <NumberField
                                    :id="'qty_' + index"
                                    v-model="item.qty"
                                    label="Kuantitas Transfer"
                                    placeholder="Misal: 10"
                                    min="0.01"
                                    step="any"
                                    class="sm"
                                    :class="{
                                        'is-invalid': form.errors[`items.${index}.qty`],
                                    }"
                                    :error="form.errors[`items.${index}.qty`]"
                                    required
                                />
                            </div>

                            <div class="col-span-12 sm:col-span-6">
                                <div class="text-xs font-semibold text-slate-700 mb-1">
                                    Stok Saat Ini (Asal)
                                </div>
                                <div
                                    class="h-[30px] flex items-center px-2.5 bg-slate-100 border border-slate-200 rounded text-xs font-semibold text-slate-700"
                                >
                                    {{ item.system_qty }} {{ item.uom }}
                                </div>
                            </div>
                        </div>

                        <!-- Real-time calculation bar -->
                        <div
                            class="flex items-center justify-between pt-1 border-t border-dashed border-slate-100 text-[11px] bg-slate-50/50 p-2 rounded"
                        >
                            <div class="text-slate-500">
                                Stok Asal Sekarang:
                                <span class="font-bold text-slate-700">
                                    {{ item.system_qty }} {{ item.uom }}
                                </span>
                            </div>

                            <div class="text-right">
                                Sisa Stok Asal:
                                <span
                                    class="font-bold ml-1"
                                    :class="
                                        Number(item.system_qty || 0) - Number(item.qty || 0) < 0
                                            ? 'text-danger'
                                            : 'text-emerald-600'
                                    "
                                >
                                    {{
                                        (Number(item.system_qty || 0) - Number(item.qty || 0))
                                            .toFixed(2)
                                            .replace(/\.00$/, '')
                                    }}
                                    {{ item.uom }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan Total Mutasi -->
                <div
                    v-if="form.items.length > 0"
                    class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-lg mt-2"
                >
                    <div>
                        <div class="text-xs text-slate-500">Total Barang:</div>
                        <div class="font-bold text-xs text-slate-800">
                            {{ form.items.length }} Item
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-slate-500">Total Kuantitas Transfer:</div>
                        <div class="font-bold text-sm text-slate-800">
                            {{ totalQtyTransfer }} Unit
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Reusable Multi-Select Item Picker Modal -->
        <ItemPickerModal
            :show="showItemPicker"
            :outlet-id="form.from_outlet_id"
            :already-selected-ids="form.items.map(i => i.inventory_item_id)"
            title="Pilih Barang Mutasi / Transfer"
            @close="showItemPicker = false"
            @selected="onItemsSelected"
        />

        <Teleport v-if="isMounted" to="#popUpFooter">
            <button
                type="button"
                class="btn btn-flat"
                :disabled="form.processing"
                @click="handleCancel"
            >
                Batal
            </button>
            <button
                type="button"
                class="btn btn-main"
                :disabled="
                    form.processing ||
                    form.items.length === 0 ||
                    !form.from_outlet_id ||
                    !form.to_outlet_id
                "
                @click="submit"
            >
                {{ isEdit ? 'Simpan Perubahan' : 'Simpan sebagai Draf' }}
            </button>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faTrash, faPlus } from '@fortawesome/free-solid-svg-icons'
import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard'
import { useAuth } from '@/Composable/useAuth'
import SearchableDropdownField from '@/Components/Form/SearchableDropdownField.vue'
import TextareaField from '@/Components/Form/TextareaField.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import ItemPickerModal from '@/Components/Inventory/ItemPickerModal.vue'

const props = defineProps({
    transferData: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['refresh'])
const { outlets: userOutlets, selectedOutlet } = useAuth()
const isMounted = ref(false)
const showItemPicker = ref(false)

const form = useForm({
    from_outlet_id:
        selectedOutlet.value?.id ||
        (userOutlets.value?.length === 1 ? userOutlets.value[0].id : ''),
    to_outlet_id: '',
    notes: '',
    items: [],
})

const { handleCancel, forceClose } = useFormDirtyGuard({ form })

const isEdit = computed(() => !!props.transferData)

const fromOutletOptions = computed(() =>
    (userOutlets.value || []).map(store => ({
        label: store.name,
        value: String(store.id),
    }))
)

const toOutletOptions = computed(() =>
    (userOutlets.value || [])
        .filter(store => String(store.id) !== String(form.from_outlet_id))
        .map(store => ({
            label: store.name,
            value: String(store.id),
        }))
)

const totalQtyTransfer = computed(() => {
    const total = form.items.reduce((sum, item) => sum + Number(item.qty || 0), 0)
    return total.toFixed(2).replace(/\.00$/, '')
})

watch(
    () => form.from_outlet_id,
    (newVal, oldVal) => {
        if (oldVal && newVal !== oldVal) {
            form.items = []
            if (form.to_outlet_id === newVal) {
                form.to_outlet_id = ''
            }
        }
    }
)

onMounted(() => {
    isMounted.value = true
    form.clearErrors()

    if (props.transferData) {
        form.from_outlet_id = props.transferData.from_outlet_id
            ? String(props.transferData.from_outlet_id)
            : ''
        form.to_outlet_id = props.transferData.to_outlet_id
            ? String(props.transferData.to_outlet_id)
            : ''
        form.notes = props.transferData.notes || ''
        form.items = (props.transferData.items || []).map(i => ({
            inventory_item_id: i.inventory_item_id,
            name: i.inventory_item?.name || i.inventoryItem?.name || '-',
            sku: i.inventory_item?.sku || i.inventoryItem?.sku || '-',
            uom: i.inventory_item?.uom?.name || i.inventoryItem?.uom?.name || '-',
            system_qty: Number(i.current_stock ?? 0),
            qty: Number(i.qty ?? 1),
        }))
    }
})

const onItemsSelected = newItems => {
    newItems.forEach(item => {
        const exists = form.items.find(i => String(i.inventory_item_id) === String(item.id))
        if (!exists) {
            form.items.unshift({
                inventory_item_id: item.id,
                name: item.name,
                sku: item.sku || '-',
                uom: item.uom?.name || item.uom_name || '-',
                system_qty: Number(item.current_stock ?? 0),
                qty: 1,
            })
        }
    })
}

const removeItem = index => {
    form.items.splice(index, 1)
}

const submit = () => {
    if (isEdit.value) {
        form.put(route('inventory.transfers.update', props.transferData.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                forceClose()
                emit('refresh')
            },
        })
    } else {
        form.post(route('inventory.transfers.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                forceClose()
                emit('refresh')
            },
        })
    }
}
</script>
