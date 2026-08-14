<template>
    <div>
        <form class="space-y-2" @submit.prevent="submit">
            <div
                class="bg-slate-50 border border-slate-200 p-3 rounded-xl space-y-2"
            >
                <SelectionGroupField
                    v-model="form.outlet_id"
                    label="Pilih Outlet"
                    :options="
                        outlets.map((o) => ({ value: o.id, label: o.name }))
                    "
                    :error="form.errors.outlet_id"
                    name="outlet_id"
                    class="sm btn-sm"
                />
            </div>

            <DropdownField
                id="inventory_item_id"
                v-model="form.inventory_item_id"
                label="Item"
                :options="itemOptions"
                :class="{ 'is-invalid': form.errors.inventory_item_id }"
                :error="form.errors.inventory_item_id"
                required
            />

            <div class="grid grid-cols-2 gap-2">
                <DropdownField
                    id="movement_type"
                    v-model="form.movement_type"
                    label="Alasan (Tipe)"
                    :options="typeOptions"
                    :class="{ 'is-invalid': form.errors.movement_type }"
                    :error="form.errors.movement_type"
                    required
                />

                <TextField
                    id="qty_change"
                    v-model="form.qty_change"
                    type="number"
                    label="Jumlah Perubahan"
                    placeholder="Misal: -2 atau 5"
                    :class="{ 'is-invalid': form.errors.qty_change }"
                    :error="form.errors.qty_change"
                    required
                />
            </div>

            <TextareaField
                id="description"
                v-model="form.description"
                label="Deskripsi Detail"
                :class="{ 'is-invalid': form.errors.description }"
                :error="form.errors.description"
                required
            />
        </form>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <button
                type="button"
                class="btn btn-flat"
                :disabled="form.processing"
                @click="close"
            >
                Batal
            </button>
            <button
                type="button"
                class="btn btn-main"
                :disabled="form.processing"
                @click="submit"
            >
                Simpan Penyesuaian
            </button>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { inject } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TextField from '@/Components/Form/TextField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue';
import { usePopUpStore } from '@/store/popup';

const popUpStore = usePopUpStore();

const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    outlets: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    outlet_id: '',
    inventory_item_id: '',
    movement_type: '',
    qty_change: '',
    description: '',
});

const outletOptions = computed(() =>
    props.outlets.map((o) => ({
        label: o.name,
        value: o.id,
    })),
);

const itemOptions = computed(() =>
    props.items.map((i) => ({
        label: `${i.name} (Stok: ${i.current_stock} ${i.uom})`,
        value: i.id,
    })),
);

const typeOptions = [
    { label: 'Waste (Terbuang/Rusak)', value: 'waste' },
    { label: 'Expired (Kedaluwarsa)', value: 'expired' },
    { label: 'Lost (Hilang)', value: 'lost' },
    { label: 'Correction (Koreksi Salah Input)', value: 'correction' },
    { label: 'Production (Produksi)', value: 'production' },
    { label: 'Other (Lainnya)', value: 'other' },
];

onMounted(() => {
    form.reset();
});

const close = () => {
    form.clearErrors();
    popUpStore.close();
};

const submit = () => {
    form.post(route('inventory.adjustments.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => close(),
    });
};
</script>
