<template>
    <div class="space-y-3.5 pb-16">
        <!-- Banner Petunjuk Ramah Pedagang Mikro -->
        <div class="p-3 bg-blue-50/80 border border-blue-200/80 rounded-xl flex items-start gap-2.5 text-xs text-blue-900 leading-relaxed">
            <FontAwesomeIcon :icon="faInfoCircle" class="text-blue-500 mt-0.5 text-sm shrink-0" />
            <div>
                <p class="font-bold text-blue-950">Atur Saldo Awal Persediaan Tokomu</p>
                <p class="text-[11px] text-blue-800/90 mt-0.5">
                    Halaman ini digunakan untuk mencatat stok fisik pertama kali dan modal beli barang. Jika barang hanya memiliki stok awal (belum pernah ada transaksi kasir), kamu juga bisa memperbarui jumlah dan modalnya di sini.
                </p>
            </div>
        </div>

        <!-- Pilihan Outlet (Jika Multi-Outlet dan belum terkunci di navbar) -->
        <div v-if="outlets.length > 1" class="bg-slate-50/60 p-3 rounded-xl border border-slate-200 space-y-1.5">
            <label class="block text-xs font-bold text-slate-700">Outlet Tujuan</label>
            <div class="max-w-xs">
                <SearchableDropdownField
                    v-model="form.outlet_id"
                    :options="outletOptions"
                    placeholder="Pilih Outlet..."
                    :error="form.errors.outlet_id"
                    :disabled="form.items.length > 0"
                    required
                />
            </div>
            <p v-if="form.items.length > 0" class="text-[11px] text-slate-400">
                Outlet dikunci saat sudah ada barang di daftar. Hapus seluruh baris barang jika ingin beralih outlet.
            </p>
        </div>

        <!-- Header Aksi & Pemilih Barang -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-1 border-t border-slate-100">
            <div>
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    Daftar Barang ({{ form.items.length }})
                </h3>
                <p class="text-[11px] text-slate-500">
                    Masukkan jumlah fisik barang saat ini dan modal belinya.
                </p>
            </div>

            <div>
                <button
                    type="button"
                    class="btn btn-main btn-sm h-[32px] inline-flex items-center gap-1.5 cursor-pointer"
                    :disabled="!form.outlet_id"
                    @click="showItemPicker = true"
                >
                    <FontAwesomeIcon :icon="faPlus" />
                    <span>Pilih Barang</span>
                </button>
            </div>
        </div>

        <!-- Empty State jika belum ada item -->
        <div
            v-if="form.items.length === 0"
            class="text-center py-10 px-4 border border-dashed border-slate-300 rounded-xl bg-slate-50/50 space-y-2.5"
        >
            <div class="size-10 rounded-full bg-slate-100 mx-auto flex items-center justify-center text-slate-400">
                <FontAwesomeIcon :icon="faBoxesStacked" class="text-lg" />
            </div>
            <div>
                <p class="text-xs font-bold text-slate-700">Belum ada barang yang dipilih</p>
                <p class="text-[11px] text-slate-400 max-w-sm mx-auto mt-0.5">
                    Klik tombol <strong>"Pilih Barang"</strong> di atas untuk memasukkan barang-barang yang ingin diatur stok awalnya.
                </p>
            </div>
            <button
                type="button"
                class="btn btn-outline-secondary btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer mt-1"
                :disabled="!form.outlet_id"
                @click="showItemPicker = true"
            >
                <FontAwesomeIcon :icon="faPlus" />
                <span>Pilih Barang Sekarang</span>
            </button>
        </div>

        <!-- Grid Table Matrix -->
        <div v-else class="border border-slate-200 rounded-xl overflow-hidden bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs min-w-[640px]">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-semibold">
                            <th class="p-2.5 w-10 text-center">No</th>
                            <th class="p-2.5 min-w-[200px]">Barang & Satuan</th>
                            <th class="p-2.5 w-32 text-center">Status</th>
                            <th class="p-2.5 w-36">Jumlah Fisik (Qty)</th>
                            <th class="p-2.5 w-40">Modal Beli Satuan (Rp)</th>
                            <th class="p-2.5 w-36 text-right">Total Modal</th>
                            <th class="p-2.5 w-12 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="(item, idx) in form.items"
                            :key="item.inventory_item_id"
                            class="hover:bg-slate-50/60 transition-colors"
                        >
                            <td class="p-2.5 text-center text-slate-400 font-medium">
                                {{ idx + 1 }}
                            </td>
                            <td class="p-2.5">
                                <div class="font-bold text-slate-800 text-xs">
                                    {{ item.name }}
                                </div>
                                <div class="text-[11px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                    <span v-if="item.sku">SKU: {{ item.sku }}</span>
                                    <span v-if="item.sku && item.barcode">•</span>
                                    <span v-if="item.barcode">Barcode: {{ item.barcode }}</span>
                                    <span>(Satuan: {{ item.uom || 'Pcs' }})</span>
                                </div>
                            </td>
                            <td class="p-2.5 text-center">
                                <span
                                    v-if="item.is_initial_only"
                                    class="badge badge-warning text-[10px] whitespace-nowrap"
                                    title="Stok awal sebelumnya, dapat kamu perbarui"
                                >
                                    Ubah Stok Awal
                                </span>
                                <span
                                    v-else
                                    class="badge badge-success text-[10px] whitespace-nowrap"
                                >
                                    Barang Baru
                                </span>
                            </td>
                            <td class="p-2.5">
                                <TextField
                                    v-model="item.qty"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    placeholder="0"
                                    size="sm"
                                    :error="form.errors[`items.${idx}.qty`]"
                                />
                            </td>
                            <td class="p-2.5">
                                <TextField
                                    v-model="item.purchase_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0"
                                    size="sm"
                                    :error="form.errors[`items.${idx}.purchase_price`]"
                                />
                            </td>
                            <td class="p-2.5 text-right font-bold text-slate-700">
                                {{ formatCurrency((parseFloat(item.qty) || 0) * (parseFloat(item.purchase_price) || 0)) }}
                            </td>
                            <td class="p-2.5 text-center">
                                <button
                                    type="button"
                                    class="text-slate-400 hover:text-danger p-1 transition-colors cursor-pointer"
                                    title="Hapus dari daftar"
                                    @click="removeItem(idx)"
                                >
                                    <FontAwesomeIcon :icon="faTrash" class="text-xs" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sticky Footer Actions Teleported to #popUpFooter -->
        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex flex-col sm:flex-row items-center justify-between w-full gap-2">
                <div class="text-xs text-slate-600 flex items-center gap-3">
                    <div>
                        Total Barang: <strong class="text-slate-800">{{ form.items.length }}</strong>
                    </div>
                    <span>•</span>
                    <div>
                        Total Nilai Modal: <strong class="text-emerald-600">{{ formatCurrency(totalInventoryValue) }}</strong>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="btn btn-flat btn-sm"
                        :disabled="form.processing"
                        @click="handleCancel"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="btn btn-main btn-sm"
                        :disabled="form.processing || form.items.length === 0"
                        @click="submit"
                    >
                        <FontAwesomeIcon :icon="faSave" class="mr-1" />
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Stok Awal' }}</span>
                    </button>
                </div>
            </div>
        </Teleport>

        <!-- Dialog Pemilih Barang Reusable -->
        <ItemPickerModal
            :show="showItemPicker"
            :outlet-id="form.outlet_id"
            :api-url="route('inventories.stocks.initial-stock.eligible-items')"
            :already-selected-ids="form.items.map(i => i.inventory_item_id)"
            title="Pilih Barang untuk Stok Awal"
            @close="showItemPicker = false"
            @selected="onItemsSelected"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faPlus,
    faTrash,
    faBoxesStacked,
    faInfoCircle,
    faSave,
} from '@fortawesome/free-solid-svg-icons'
import { useAuth } from '@/Composable/useAuth'
import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard'
import ItemPickerModal from '@/Components/Inventory/ItemPickerModal.vue'
import SearchableDropdownField from '@/Components/Form/SearchableDropdownField.vue'
import TextField from '@/Components/Form/TextField.vue'

const props = defineProps({
    outletId: {
        type: [String, Number],
        default: null,
    },
})

const { outlets, selectedOutlet } = useAuth()
const isMounted = ref(false)
const showItemPicker = ref(false)

const initialOutletId = props.outletId || selectedOutlet.value?.id || outlets.value?.[0]?.id || ''

const form = useForm({
    outlet_id: initialOutletId,
    items: [],
})

const { handleCancel, forceClose } = useFormDirtyGuard({ form })

onMounted(() => {
    isMounted.value = true
})

const outletOptions = computed(() => {
    return (outlets.value || []).map(o => ({
        value: String(o.id),
        label: o.name,
    }))
})

const totalInventoryValue = computed(() => {
    return form.items.reduce((sum, item) => {
        const qty = parseFloat(item.qty) || 0
        const price = parseFloat(item.purchase_price) || 0
        return sum + (qty * price)
    }, 0)
})

const formatCurrency = amount => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(amount || 0)
}

const onItemsSelected = selectedItems => {
    selectedItems.forEach(item => {
        const existingIdx = form.items.findIndex(i => i.inventory_item_id === item.id)
        if (existingIdx === -1) {
            form.items.push({
                inventory_item_id: item.id,
                name: item.name,
                sku: item.sku || '',
                barcode: item.barcode || '',
                uom: item.uom || 'Pcs',
                qty: item.current_stock > 0 ? String(item.current_stock) : '',
                purchase_price: item.current_cost > 0 ? String(item.current_cost) : '',
                is_initial_only: Boolean(item.is_initial_only),
            })
        }
    })
}

const removeItem = idx => {
    form.items.splice(idx, 1)
}

const submit = () => {
    form.post(route('inventories.stocks.initial-stock.batch'), {
        preserveScroll: true,
        onSuccess: () => {
            forceClose()
            router.reload({ only: ['stocks', 'summary'] })
        },
    })
}
</script>
