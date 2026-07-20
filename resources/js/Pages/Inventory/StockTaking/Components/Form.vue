<template>
    <PopUpPage :class="{ show: show }" :title="isApprove ? 'Setujui Hasil Opname' : 'Mulai/Update Opname'" size="max-w-4xl" @close="close">
        <form @submit.prevent="submit" class="p-4 space-y-4">
            <div v-if="opname" class="mb-4 bg-gray-50 p-4 rounded-lg">
                <p><strong>Nomor Opname:</strong> {{ opname.opname_number }}</p>
                <p><strong>Status:</strong> {{ opname.status }}</p>
            </div>
            
            <TextareaField
                id="notes"
                v-model="form.notes"
                label="Catatan"
                :class="{ 'is-invalid': form.errors.notes }"
                :feedback="form.errors.notes"
                :disabled="isApprove"
            />
            
            <div class="mt-6 border-t pt-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Pencatatan Fisik Stok</h3>
                    <button v-if="!isApprove" type="button" class="btn btn-outline-main btn-sm" @click="loadAllItems">
                        Muat Semua Item
                    </button>
                </div>
                
                <div v-if="form.items.length === 0" class="text-center py-4 text-gray-500 border rounded-lg">
                    Klik "Muat Semua Item" untuk memulai opname.
                </div>
                
                <div v-else class="space-y-3">
                    <div v-for="(item, index) in form.items" :key="index" class="flex gap-4 items-center border p-3 rounded-lg" :class="{'bg-red-50': isApprove && item.actual_qty !== item.system_qty}">
                        <div class="flex-1">
                            <div class="font-semibold">{{ item.name }}</div>
                            <div class="text-sm text-gray-500">Sistem: {{ formatQuantity(item.system_qty) }} {{ item.uom }}</div>
                        </div>
                        <div class="w-40">
                            <TextField
                                v-model="item.actual_qty"
                                type="number"
                                label="Fisik"
                                min="0"
                                :disabled="isApprove"
                            />
                        </div>
                        <div class="w-24 text-right font-bold" :class="item.actual_qty - item.system_qty < 0 ? 'text-danger' : (item.actual_qty - item.system_qty > 0 ? 'text-success' : 'text-gray-400')">
                            Selisih: {{ formatQuantity(Number(item.actual_qty || 0) - Number(item.system_qty)) }}
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <template #footer>
            <button type="button" class="btn btn-flat" @click="close" :disabled="form.processing">
                Batal
            </button>
            <button v-if="isApprove" type="button" class="btn btn-main" @click="approve" :disabled="form.processing">
                Setujui & Sesuaikan Stok
            </button>
            <button v-else type="button" class="btn btn-main" @click="submit" :disabled="form.processing || form.items.length === 0">
                Simpan Opname
            </button>
        </template>
    </PopUpPage>
</template>

<script setup>
import { watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { formatQuantity } from '@/Composable/number-format';
import PopUpPage from '@/Components/UI/PopUpPage.vue';
import TextField from '@/Components/Form/TextField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    opname: {
        type: Object,
        default: null,
    },
    items: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    notes: '',
    items: [],
});

const isApprove = computed(() => {
    return props.opname?.status === 'pending_approval';
});

const loadAllItems = () => {
    form.items = props.items.map(i => ({
        inventory_item_id: i.id,
        name: i.name,
        uom: i.uom,
        system_qty: i.system_qty,
        actual_qty: i.system_qty, // default to system qty
    }));
};

watch(
    () => props.opname,
    (data) => {
        form.reset();
        if (data) {
            form.notes = data.notes || '';
            // Load saved items if any
            if (data.items && data.items.length > 0) {
                form.items = data.items.map(i => ({
                    inventory_item_id: i.inventory_item_id,
                    name: i.inventory_item?.name || 'Unknown',
                    uom: i.inventory_item?.uom?.name || '-',
                    system_qty: i.system_qty,
                    actual_qty: i.actual_qty,
                }));
            }
        }
    },
    { immediate: true }
);

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen && !props.opname) {
            form.reset();
            form.items = [];
        }
    }
);


const close = () => {
    form.clearErrors();
    emit('close');
};

const submit = () => {
    if (props.opname?.id) {
        form.put(route('inventory.opnames.update', props.opname.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => close(),
        });
    } else {
        form.post(route('inventory.opnames.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => close(),
        });
    }
};

const approve = () => {
    form.post(route('inventory.opnames.approve', props.opname.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => close(),
    });
};
</script>
