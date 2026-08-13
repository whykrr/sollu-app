<template>
    <form @submit.prevent="submit" class="space-y-2">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <DropdownField
                id="supplier_id"
                v-model="form.supplier_id"
                label="Supplier"
                placeholder="Pilih Supplier"
                :options="supplierOptions"
                :class="{ 'is-invalid': form.errors.supplier_id }"
                :error="form.errors.supplier_id"
                required
            />

            <div>
                <label class="block text-sm font-medium text-gray-700"
                    >Outlet Tujuan <span class="text-danger">*</span></label
                >
                <AsyncOutletDropdown
                    v-model="form.outlet_id"
                    placeholder="Pilih Outlet Tujuan"
                    :class="{ 'is-invalid': form.errors.outlet_id }"
                    required
                />
                <div v-if="form.errors.outlet_id" class="invalid-feedback">
                    {{ form.errors.outlet_id }}
                </div>
            </div>

            <TextField
                id="order_date"
                v-model="form.order_date"
                type="date"
                label="Tanggal Pesan"
                :class="{ 'is-invalid': form.errors.order_date }"
                :error="form.errors.order_date"
                required
            />

            <TextField
                id="expected_date"
                v-model="form.expected_date"
                type="date"
                label="Tanggal Diharapkan"
                :class="{ 'is-invalid': form.errors.expected_date }"
                :error="form.errors.expected_date"
            />
        </div>

        <TextareaField
            id="notes"
            v-model="form.notes"
            label="Catatan"
            :class="{ 'is-invalid': form.errors.notes }"
            :error="form.errors.notes"
        />

        <!-- Item Section -->
        <div class="mt-4 border-t pt-2">
            <h3 class="text-lg font-semibold mb-2">Pilih Barang</h3>

            <!-- Search Input -->
            <div class="mb-2">
                <input
                    type="text"
                    v-model="searchQuery"
                    class="form-input text-sm w-full rounded-lg border-gray-300"
                    placeholder="Cari nama barang atau bahan baku..."
                    @input="onSearchInput"
                />
                <div v-if="isSearching" class="text-xs text-slate-500 py-1">
                    Mencari...
                </div>

                <!-- Search Results -->
                <div
                    v-if="searchQuery || searchResults.length > 0"
                    class="border border-gray-200 rounded-lg p-2 max-h-48 overflow-y-auto space-y-1 mt-1 bg-white shadow-sm"
                >
                    <div
                        v-for="item in searchResults"
                        :key="item.id"
                        @click="selectItem(item)"
                        class="flex items-center justify-between p-2 hover:bg-slate-50 rounded cursor-pointer border-b last:border-0 border-gray-100"
                    >
                        <div>
                            <div class="font-medium text-sm text-slate-800">
                                {{ item.name }}
                                <span class="text-xs text-slate-500"
                                    >({{ item.uom?.name || '-' }})</span
                                >
                            </div>
                            <div v-if="item.sku" class="text-xs text-slate-400">
                                SKU: {{ item.sku }}
                            </div>
                        </div>
                        <div>
                            <span
                                v-if="item.is_supplied"
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 gap-1"
                            >
                                <FontAwesomeIcon :icon="faCheck" />
                                Terdaftar di Supplier Ini
                            </span>
                        </div>
                    </div>
                    <div
                        v-if="searchResults.length === 0 && !isSearching"
                        class="text-xs text-slate-500 text-center py-4"
                    >
                        Barang tidak ditemukan.
                    </div>
                </div>
            </div>

            <!-- Selected Items List -->
            <h3 class="text-md font-semibold mb-2 mt-4">
                Daftar Barang yang Dipesan
            </h3>

            <div
                v-if="form.items.length === 0"
                class="text-center py-6 text-gray-500 border rounded-lg bg-gray-50/50"
            >
                Belum ada item ditambahkan.
            </div>

            <div v-else class="space-y-2">
                <div
                    v-for="(item, index) in form.items"
                    :key="index"
                    class="flex flex-col md:flex-row gap-2 md:items-center border px-3 py-2 rounded-lg bg-white"
                >
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-slate-800 truncate">
                            {{ item.name }}
                        </div>
                        <div class="mt-1 max-w-40">
                            <DropdownField
                                :id="'uom_id_' + index"
                                v-model="item.uom_id"
                                :options="uomOptions"
                                placeholder="Pilih Satuan"
                                class="sm"
                                required
                            />
                        </div>
                    </div>
                    <div class="w-full md:w-32">
                        <TextField
                            :id="'qty_ordered_' + index"
                            v-model="item.qty_ordered"
                            type="number"
                            label="Quantity"
                            class="sm"
                            min="1"
                            required
                        />
                    </div>
                    <div class="w-full md:w-40">
                        <TextField
                            :id="'purchase_price_' + index"
                            v-model="item.purchase_price"
                            type="number"
                            label="Harga Satuan"
                            min="0"
                            class="sm"
                            required
                        />
                    </div>
                    <div class="pt-6 md:pt-0 shrink-0">
                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm"
                            @click="removeItem(index)"
                        >
                            <FontAwesomeIcon :icon="faTrash" />
                        </button>
                    </div>
                </div>

                <div
                    class="flex justify-between items-center border-t mt-4 pt-4"
                >
                    <div class="font-bold text-lg text-slate-800">
                        Total Pembelian:
                    </div>
                    <div class="font-bold text-xl text-main">
                        {{ formatCurrency(totalAmount) }}
                    </div>
                </div>
            </div>
        </div>
    </form>

    <Teleport v-if="isMounted" to="#popUpFooter">
        <button
            type="button"
            class="btn btn-flat"
            @click="close"
            :disabled="form.processing"
        >
            Batal
        </button>
        <button
            type="button"
            class="btn btn-main"
            @click="submit"
            :disabled="form.processing || form.items.length === 0"
        >
            Simpan PO
        </button>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { debounce } from 'lodash';
import { usePopUpStore } from '@/store/popup';
import TextField from '@/Components/Form/TextField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import AsyncOutletDropdown from '@/Components/Form/AsyncOutletDropdown.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faCheck, faTrash } from '@fortawesome/free-solid-svg-icons';

const popUpStore = usePopUpStore();

const props = defineProps({
    purchase: {
        type: Object,
        default: null,
    },
    suppliers: {
        type: Array,
        default: () => [],
    },
    uoms: {
        type: Array,
        default: () => [],
    },
});

const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});

const form = useForm({
    supplier_id: '',
    outlet_id: '',
    order_date: new Date().toISOString().split('T')[0],
    expected_date: '',
    notes: '',
    items: [],
});

const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);

const supplierOptions = computed(() =>
    props.suppliers.map((s) => ({ label: s.name, value: s.id })),
);
const uomOptions = computed(() =>
    props.uoms.map((u) => ({ label: u.name, value: u.id })),
);

const totalAmount = computed(() => {
    return form.items.reduce(
        (sum, item) =>
            sum +
            Number(item.qty_ordered || 0) * Number(item.purchase_price || 0),
        0,
    );
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
    }).format(value);
};

const onSearchInput = debounce(async () => {
    if (!searchQuery.value) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;
    try {
        const response = await axios.get(
            route('inventory.purchases.search-items'),
            {
                params: {
                    search: searchQuery.value,
                    supplier_id: form.supplier_id,
                },
            },
        );
        searchResults.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        isSearching.value = false;
    }
}, 500);

const selectItem = (item) => {
    // Check if item already exists in form.items
    const exists = form.items.find((i) => i.inventory_item_id === item.id);
    if (exists) {
        // optionally increment qty
        exists.qty_ordered = Number(exists.qty_ordered) + 1;
    } else {
        form.items.push({
            inventory_item_id: item.id,
            name: item.name,
            uom_id: item.uom_id || '', // Default to item's inventory UOM
            uom_name: item.uom?.name || '',
            qty_ordered: 1,
            purchase_price: 0,
        });
    }
    // Clear search
    searchQuery.value = '';
    searchResults.value = [];
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

// Re-search when supplier changes if there's an active query
watch(
    () => form.supplier_id,
    () => {
        if (searchQuery.value) {
            onSearchInput();
        }
    },
);

watch(
    () => props.purchase,
    (data) => {
        form.reset();
        searchQuery.value = '';
        searchResults.value = [];

        if (data) {
            form.supplier_id = data.supplier_id || '';
            form.outlet_id = data.outlet_id || '';
            form.order_date =
                data.order_date || new Date().toISOString().split('T')[0];
            form.expected_date = data.expected_date || '';
            form.notes = data.notes || '';

            // Map existing items properly for display
            if (data.items && data.items.length > 0) {
                form.items = data.items.map((i) => ({
                    inventory_item_id: i.inventory_item_id,
                    name: i.inventory_item?.name || 'Unknown Item',
                    uom_id: i.uom_id || i.inventory_item?.uom_id || '',
                    uom_name: i.uom?.name || i.inventory_item?.uom?.name || '',
                    qty_ordered: i.qty_ordered,
                    purchase_price: i.purchase_price,
                }));
            } else {
                form.items = [];
            }
        }
    },
    { immediate: true },
);

const close = () => {
    form.clearErrors();
    popUpStore.close();
};

const submit = () => {
    if (props.purchase?.id) {
        form.put(route('inventory.purchases.update', props.purchase.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => close(),
        });
    } else {
        form.post(route('inventory.purchases.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => close(),
        });
    }
};
</script>
