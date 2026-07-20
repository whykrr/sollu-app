<template>
    <PopUpPage :class="{ show: show }" title="Terima Barang (Receive)" size="max-w-3xl" @close="close">
        <form @submit.prevent="submit" class="p-4 space-y-4">
            <div v-if="purchase" class="mb-4 bg-gray-50 p-4 rounded-lg">
                <p><strong>Nomor PO:</strong> {{ purchase.po_number }}</p>
                <p><strong>Supplier:</strong> {{ purchase.supplier_name }}</p>
                <p><strong>Outlet:</strong> {{ purchase.outlet_name }}</p>
            </div>
            
            <div class="border-t pt-4">
                <h3 class="text-lg font-semibold mb-4">Input Penerimaan</h3>
                
                <div v-if="form.items.length === 0" class="text-center py-4 text-gray-500 border rounded-lg">
                    Data item tidak ditemukan.
                </div>
                
                <div v-else class="space-y-3">
                    <div v-for="(item, index) in form.items" :key="index" class="flex gap-4 items-center border p-3 rounded-lg">
                        <div class="flex-1">
                            <div class="font-semibold">{{ item.name }}</div>
                            <div class="text-sm text-gray-500">Dipesan: {{ formatQuantity(item.qty_ordered) }} | Diterima sblm: {{ formatQuantity(item.qty_received_before || 0) }}</div>
                        </div>
                        <div class="w-40">
                            <TextField
                                v-model="item.qty_received"
                                type="number"
                                label="Jml Diterima Saat Ini"
                                min="0"
                                :max="item.qty_ordered - (item.qty_received_before || 0)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <template #footer>
            <button type="button" class="btn btn-flat" @click="close" :disabled="form.processing">
                Batal
            </button>
            <button type="button" class="btn btn-main" @click="submit" :disabled="form.processing || form.items.length === 0">
                Simpan Penerimaan
            </button>
        </template>
    </PopUpPage>
</template>

<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { formatQuantity } from '@/Composable/number-format';
import PopUpPage from '@/Components/UI/PopUpPage.vue';
import TextField from '@/Components/Form/TextField.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    purchase: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    items: [],
});

watch(
    () => props.purchase,
    (data) => {
        form.reset();
        if (data && data.items) {
            form.items = data.items.map(i => ({
                id: i.id,
                inventory_item_id: i.inventory_item_id,
                name: i.inventory_item?.name || 'Unknown',
                qty_ordered: i.qty_ordered,
                qty_received_before: i.qty_received,
                qty_received: 0,
            }));
        } else {
            form.items = [];
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
        form.post(route('inventory.purchases.receive', props.purchase.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => close(),
        });
    }
};
</script>
