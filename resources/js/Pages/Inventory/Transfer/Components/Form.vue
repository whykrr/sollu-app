<template>
    <PopUpPage :class="{ show: show }" title="Transfer Stok" size="max-w-4xl" @close="close">
        <form @submit.prevent="submit" class="p-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <DropdownField
                    id="to_outlet_id"
                    v-model="form.to_outlet_id"
                    label="Outlet Tujuan"
                    :options="outletOptions"
                    :class="{ 'is-invalid': form.errors.to_outlet_id }"
                    :feedback="form.errors.to_outlet_id"
                    required
                />
            </div>
            
            <TextareaField
                id="notes"
                v-model="form.notes"
                label="Catatan Transfer"
                :class="{ 'is-invalid': form.errors.notes }"
                :feedback="form.errors.notes"
            />
            
            <div class="mt-6 border-t pt-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Item yang Ditransfer</h3>
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
                        <div class="w-40">
                            <TextField
                                v-model="item.qty"
                                type="number"
                                label="Quantity Transfer"
                                min="1"
                                required
                            />
                        </div>
                        <div class="pt-7">
                            <button type="button" class="btn btn-flat text-danger" @click="removeItem(index)">
                                Hapus
                            </button>
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
                Ajukan Transfer
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
    to_outlet_id: '',
    notes: '',
    items: [],
});

const outletOptions = computed(() => props.outlets.map(o => ({ label: o.name, value: o.id })));
const itemOptions = computed(() => props.items.map(i => ({ 
    label: `${i.name} (Stok: ${i.current_stock} ${i.uom})`, 
    value: i.id 
})));

const addItem = () => {
    form.items.push({
        inventory_item_id: '',
        qty: 1,
    });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            form.reset();
        }
    }
);

const close = () => {
    form.clearErrors();
    emit('close');
};

const submit = () => {
    form.post(route('inventory.transfers.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => close(),
    });
};
</script>
