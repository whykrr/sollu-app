<template>
    <PopUpPage
        :class="{ show: show }"
        title="Buat Draft Penyesuaian Stok"
        size="xl"
        @close="close"
    >
        <form @submit.prevent="submit" class="space-y-2">
            <div class="grid grid-cols-2 gap-2">
                <DropdownField
                    id="outlet_id"
                    v-model="form.outlet_id"
                    label="Outlet"
                    placeholder="Pilih outlet..."
                    :options="outletOptions"
                    :class="{ 'is-invalid': form.errors.outlet_id }"
                    :feedback="form.errors.outlet_id"
                    required
                />
                <DropdownField
                    id="reason"
                    v-model="form.reason"
                    label="Alasan Utama"
                    placeholder="Pilih alasan..."
                    :options="reasonOptions"
                    :class="{ 'is-invalid': form.errors.reason }"
                    :feedback="form.errors.reason"
                    required
                />
            </div>

            <TextareaField
                id="notes"
                v-model="form.notes"
                label="Catatan Opsional"
                :class="{ 'is-invalid': form.errors.notes }"
                :feedback="form.errors.notes"
                rows="2"
            />

            <div>
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-gray-700">Daftar Item</h3>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline"
                        @click="addItem"
                    >
                        + Tambah Item
                    </button>
                </div>

                <div v-if="form.errors.items" class="text-danger text-sm mb-2">
                    {{ form.errors.items }}
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(item, index) in form.items"
                        :key="index"
                        class="p-4 border rounded relative bg-gray-50"
                    >
                        <button
                            type="button"
                            class="absolute top-2 right-2 text-danger hover:text-red-700 text-sm z-10"
                            @click="removeItem(index)"
                        >
                            Hapus
                        </button>

                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-12 md:col-span-5">
                                <DropdownField
                                    :id="'item_' + index"
                                    v-model="item.inventory_item_id"
                                    label="Pilih Item"
                                    placeholder="Pilih item..."
                                    :options="itemOptions"
                                    :class="{
                                        'is-invalid':
                                            form.errors[
                                                `items.${index}.inventory_item_id`
                                            ],
                                    }"
                                    :feedback="
                                        form.errors[
                                            `items.${index}.inventory_item_id`
                                        ]
                                    "
                                    required
                                />
                                <div
                                    v-if="item.inventory_item_id"
                                    class="text-xs text-gray-500 mt-1"
                                >
                                    Stok saat ini di outlet:
                                    {{
                                        getCurrentStock(item.inventory_item_id)
                                    }}
                                </div>
                            </div>

                            <div class="col-span-6 md:col-span-3">
                                <NumberField
                                    :id="'qty_' + index"
                                    v-model="item.qty_change"
                                    label="Perubahan Qty (+/-)"
                                    placeholder="Misal: -2 atau 5"
                                    :class="{
                                        'is-invalid':
                                            form.errors[
                                                `items.${index}.qty_change`
                                            ],
                                    }"
                                    :feedback="
                                        form.errors[`items.${index}.qty_change`]
                                    "
                                    required
                                />
                            </div>

                            <div
                                class="col-span-6 md:col-span-4"
                                v-if="item.qty_change > 0"
                            >
                                <NumberField
                                    :id="'unit_cost_' + index"
                                    v-model="item.unit_cost"
                                    label="HPP / Unit Cost (Opsional)"
                                    placeholder="Auto (Moving Avg)"
                                    min="0"
                                    :class="{
                                        'is-invalid':
                                            form.errors[
                                                `items.${index}.unit_cost`
                                            ],
                                    }"
                                    :feedback="
                                        form.errors[`items.${index}.unit_cost`]
                                    "
                                />
                            </div>

                            <div class="col-span-12">
                                <TextField
                                    :id="'desc_' + index"
                                    v-model="item.description"
                                    label="Deskripsi / Alasan"
                                    placeholder="Detail alasan untuk item ini"
                                    :class="{
                                        'is-invalid':
                                            form.errors[
                                                `items.${index}.description`
                                            ],
                                    }"
                                    :feedback="
                                        form.errors[
                                            `items.${index}.description`
                                        ]
                                    "
                                    required
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                :disabled="form.processing || form.items.length === 0"
            >
                Simpan Penyesuaian
            </button>
        </template>
    </PopUpPage>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PopUpPage from '@/Components/UI/PopUpPage.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';
import TextField from '@/Components/Form/TextField.vue';
import NumberField from '@/Components/Form/NumberField.vue';

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
    reason: '',
    notes: '',
    items: [],
});

const outletOptions = computed(() =>
    props.outlets
        .filter((o) => !o.is_stock_frozen)
        .map((o) => ({
            label: o.name,
            value: o.id,
        })),
);

const itemOptions = computed(() =>
    props.items.map((i) => ({
        label: `${i.name} (${i.uom ? i.uom.name : '-'})`,
        value: i.id,
    })),
);

const reasonOptions = [
    { label: 'Rusak / Terbuang (Waste)', value: 'waste' },
    { label: 'Kedaluwarsa (Expired)', value: 'expired' },
    { label: 'Hilang (Lost)', value: 'lost' },
    { label: 'Koreksi Salah Input (Correction)', value: 'correction' },
    { label: 'Produksi (Production)', value: 'production' },
    { label: 'Lainnya (Other)', value: 'other' },
];

const addItem = () => {
    form.items.push({
        inventory_item_id: '',
        qty_change: '',
        unit_cost: '',
        description: '',
    });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const getCurrentStock = (itemId) => {
    if (!form.outlet_id || !itemId) return 'Pilih outlet';
    const item = props.items.find((i) => i.id === itemId);
    if (!item) return '-';

    const balance = item.balances.find((b) => b.outlet_id === form.outlet_id);
    return balance ? balance.current_stock : 0;
};

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            form.reset();
            if (form.items.length === 0) {
                addItem();
            }
        }
    },
);

const close = () => {
    form.reset();
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
