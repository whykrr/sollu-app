<template>
    <PopUpPage :class="{ show: show }" title="Bahan Baku" @close="close">
        <form @submit.prevent="submit" class="space-y-2">
            <TextField
                id="name"
                v-model="form.name"
                label="Nama Bahan Baku"
                :class="{ 'is-invalid': form.errors.name }"
                :feedback="form.errors.name"
                required
            />

            <TextField
                id="sku"
                v-model="form.sku"
                label="SKU"
                :class="{ 'is-invalid': form.errors.sku }"
                :feedback="form.errors.sku"
            />

            <TextField
                id="barcode"
                v-model="form.barcode"
                label="Barcode"
                :class="{ 'is-invalid': form.errors.barcode }"
                :feedback="form.errors.barcode"
            />

            <DropdownField
                id="uom_id"
                v-model="form.uom_id"
                label="Satuan (UOM)"
                :options="uomOptions"
                :class="{ 'is-invalid': form.errors.uom_id }"
                :feedback="form.errors.uom_id"
                required
            />

            <label
                class="flex items-center justify-between border p-3 rounded-lg cursor-pointer hover:bg-slate-50 transition w-full"
            >
                <div>
                    <div class="font-bold text-sm">Lacak Inventori (Stok)</div>
                    <div class="text-xs text-slate-500">
                        Lacak stok masuk dan keluar untuk bahan baku ini.
                    </div>
                </div>
                <input
                    type="checkbox"
                    v-model="form.track_inventory"
                    class="rounded h-5 w-5 text-primary cursor-pointer"
                />
            </label>

            <TextField
                v-if="form.track_inventory"
                id="minimum_stock"
                type="number"
                v-model="form.minimum_stock"
                label="Minimum Stok"
                :class="{ 'is-invalid': form.errors.minimum_stock }"
                :feedback="form.errors.minimum_stock"
            />
        </form>

        <template #footer>
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
                :disabled="form.processing"
            >
                Simpan
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
import Switch from '@/Components/Form/Switch.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    rawMaterial: {
        type: Object,
        default: null,
    },
    uoms: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    sku: '',
    barcode: '',
    uom_id: '',
    track_inventory: true,
    minimum_stock: 0,
});

const uomOptions = computed(() => {
    return props.uoms.map((uom) => ({
        label: uom.name,
        value: uom.id,
    }));
});

watch(
    () => props.rawMaterial,
    (data) => {
        form.reset();
        if (data) {
            form.name = data.name || '';
            form.sku = data.sku || '';
            form.barcode = data.barcode || '';
            form.uom_id = data.uom_id || '';
            form.track_inventory = data.track_inventory ?? true;
            form.minimum_stock = data.minimum_stock || 0;
        }
    },
    { immediate: true },
);

const close = () => {
    form.clearErrors();
    emit('close');
};

const submit = () => {
    if (props.rawMaterial?.id) {
        form.put(route('inventory.raw-materials.update', props.rawMaterial.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => close(),
        });
    } else {
        form.post(route('inventory.raw-materials.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => close(),
        });
    }
};
</script>
