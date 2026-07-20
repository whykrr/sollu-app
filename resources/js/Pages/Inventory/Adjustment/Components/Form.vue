<template>
    <PopUpPage :class="{ show: show }" title="Buat Penyesuaian Stok" size="max-w-xl" @close="close">
        <form @submit.prevent="submit" class="p-4 space-y-4">
            <DropdownField
                id="outlet_id"
                v-model="form.outlet_id"
                label="Outlet"
                :options="outletOptions"
                :class="{ 'is-invalid': form.errors.outlet_id }"
                :feedback="form.errors.outlet_id"
                required
            />

            <DropdownField
                id="inventory_item_id"
                v-model="form.inventory_item_id"
                label="Item"
                :options="itemOptions"
                :class="{ 'is-invalid': form.errors.inventory_item_id }"
                :feedback="form.errors.inventory_item_id"
                required
            />
            
            <div class="grid grid-cols-2 gap-4">
                <DropdownField
                    id="movement_type"
                    v-model="form.movement_type"
                    label="Alasan (Tipe)"
                    :options="typeOptions"
                    :class="{ 'is-invalid': form.errors.movement_type }"
                    :feedback="form.errors.movement_type"
                    required
                />
                
                <TextField
                    id="qty_change"
                    v-model="form.qty_change"
                    type="number"
                    label="Jumlah Perubahan"
                    placeholder="Misal: -2 atau 5"
                    :class="{ 'is-invalid': form.errors.qty_change }"
                    :feedback="form.errors.qty_change"
                    required
                />
            </div>
            
            <TextareaField
                id="description"
                v-model="form.description"
                label="Deskripsi Detail"
                :class="{ 'is-invalid': form.errors.description }"
                :feedback="form.errors.description"
                required
            />
        </form>

        <template #footer>
            <button type="button" class="btn btn-flat" @click="close" :disabled="form.processing">
                Batal
            </button>
            <button type="button" class="btn btn-main" @click="submit" :disabled="form.processing">
                Simpan Penyesuaian
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
    items: {
        type: Array,
        default: () => [],
    },
    outlets: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    outlet_id: '',
    inventory_item_id: '',
    movement_type: '',
    qty_change: '',
    description: '',
});

const outletOptions = computed(() => props.outlets.map(o => ({
    label: o.name,
    value: o.id
})));

const itemOptions = computed(() => props.items.map(i => ({ 
    label: `${i.name} (Stok: ${i.current_stock} ${i.uom})`, 
    value: i.id 
})));

const typeOptions = [
    { label: 'Waste (Terbuang/Rusak)', value: 'waste' },
    { label: 'Expired (Kedaluwarsa)', value: 'expired' },
    { label: 'Correction (Koreksi Salah Input)', value: 'correction' },
    { label: 'Other (Lainnya)', value: 'other' },
];

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
    form.post(route('inventory.adjustments.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => close(),
    });
};
</script>
