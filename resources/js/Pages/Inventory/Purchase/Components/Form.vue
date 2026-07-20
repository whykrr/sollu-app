<template>
    <PopUpPage :class="{ show: show }" title="Purchase Order" size="max-w-4xl" @close="close">
        <form @submit.prevent="submit" class="p-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <DropdownField
                    id="supplier_id"
                    v-model="form.supplier_id"
                    label="Supplier"
                    :options="supplierOptions"
                    :class="{ 'is-invalid': form.errors.supplier_id }"
                    :feedback="form.errors.supplier_id"
                    required
                />
                
                <DropdownField
                    id="outlet_id"
                    v-model="form.outlet_id"
                    label="Outlet Tujuan"
                    :options="outletOptions"
                    :class="{ 'is-invalid': form.errors.outlet_id }"
                    :feedback="form.errors.outlet_id"
                    required
                />
                
                <TextField
                    id="order_date"
                    v-model="form.order_date"
                    type="date"
                    label="Tanggal Pesan"
                    :class="{ 'is-invalid': form.errors.order_date }"
                    :feedback="form.errors.order_date"
                    required
                />
                
                <TextField
                    id="expected_date"
                    v-model="form.expected_date"
                    type="date"
                    label="Tanggal Diharapkan"
                    :class="{ 'is-invalid': form.errors.expected_date }"
                    :feedback="form.errors.expected_date"
                />
            </div>
            
            <TextareaField
                id="notes"
                v-model="form.notes"
                label="Catatan"
                :class="{ 'is-invalid': form.errors.notes }"
                :feedback="form.errors.notes"
            />
            
            <div class="mt-6 border-t pt-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Daftar Item</h3>
                    <button type="button" class="btn btn-outline-main btn-sm" @click="addItem">
                        Tambah Item
                    </button>
                </div>
                
                <div v-if="form.items.length === 0" class="text-center py-4 text-gray-500 border rounded-lg">
                    Belum ada item ditambahkan.
                </div>
                
                <div v-else class="space-y-3">
                    <div v-for="(item, index) in form.items" :key="index" class="flex gap-3 items-start border p-3 rounded-lg bg-gray-50">
                        <div class="flex-1">
                            <DropdownField
                                v-model="item.inventory_item_id"
                                :options="itemOptions"
                                label="Item"
                                required
                            />
                        </div>
                        <div class="w-32">
                            <TextField
                                v-model="item.qty_ordered"
                                type="number"
                                label="Quantity"
                                min="1"
                                required
                            />
                        </div>
                        <div class="w-40">
                            <TextField
                                v-model="item.purchase_price"
                                type="number"
                                label="Harga Satuan"
                                min="0"
                                required
                            />
                        </div>
                        <div class="pt-7">
                            <button type="button" class="btn btn-flat text-danger" @click="removeItem(index)">
                                Hapus
                            </button>
                        </div>
                    </div>
                    
                    <div class="text-right font-bold text-lg mt-4">
                        Total: {{ formatCurrency(totalAmount) }}
                    </div>
                </div>
            </div>
        </form>

        <template #footer>
            <button type="button" class="btn btn-flat" @click="close" :disabled="form.processing">
                Batal
            </button>
            <button type="button" class="btn btn-main" @click="submit" :disabled="form.processing || form.items.length === 0">
                Simpan PO
            </button>
        </template>
    </PopUpPage>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PopUpPage from '@/Components/UI/PopUpPage.vue';
import TextField from '@/Components/Form/TextField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    purchase: {
        type: Object,
        default: null,
    },
    suppliers: {
        type: Array,
        default: () => [],
    },
    outlets: {
        type: Array,
        default: () => [],
    },
    items: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    supplier_id: '',
    outlet_id: '',
    order_date: new Date().toISOString().split('T')[0],
    expected_date: '',
    notes: '',
    items: [],
});

const supplierOptions = computed(() => props.suppliers.map(s => ({ label: s.name, value: s.id })));
const outletOptions = computed(() => props.outlets.map(o => ({ label: o.name, value: o.id })));
const itemOptions = computed(() => props.items.map(i => ({ label: `${i.name} (${i.uom})`, value: i.id })));

const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => sum + (Number(item.qty_ordered || 0) * Number(item.purchase_price || 0)), 0);
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const addItem = () => {
    form.items.push({
        inventory_item_id: '',
        qty_ordered: 1,
        purchase_price: 0,
    });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

watch(
    () => props.purchase,
    (data) => {
        form.reset();
        if (data) {
            form.supplier_id = data.supplier_id || '';
            form.outlet_id = data.outlet_id || '';
            form.order_date = data.order_date || new Date().toISOString().split('T')[0];
            form.expected_date = data.expected_date || '';
            form.notes = data.notes || '';
            form.items = data.items || [];
        }
    },
    { immediate: true }
);

const close = () => {
    form.clearErrors();
    emit('close');
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
