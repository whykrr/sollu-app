<template>
    <div>
        <form @submit.prevent="submit" class="space-y-2">
            <div class="grid grid-cols-2 gap-2">
                <AsyncOutletDropdown
                    id="outlet_id"
                    v-model="form.outlet_id"
                    label="Outlet"
                    placeholder="Pilih outlet..."
                    :exclude-frozen="true"
                    :class="{ 'is-invalid': form.errors.outlet_id }"
                    :feedback="form.errors.outlet_id"
                    @loaded="onOutletsLoaded"
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

            <div class="mt-2 border-t pt-2">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-bold text-gray-700">Daftar Item</h3>
                    <div class="w-72">
                        <AsyncSelectField
                            id="search_item"
                            label="Cari Item (Min. 3 huruf)"
                            placeholder="Cari nama, SKU, barcode..."
                            class="sm"
                            :api-url="
                                route('api.internal.inventory-items.search')
                            "
                            :api-params="{
                                outlet_id: form.outlet_id,
                            }"
                            :min-chars="3"
                            :disabled="!form.outlet_id"
                            @select="addItemFromSearch"
                        >
                            <template #option="{ item }">
                                <div
                                    class="flex justify-between items-center w-full"
                                >
                                    <div>
                                        <div class="font-semibold text-sm">
                                            {{ item.name }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            SKU: {{ item.sku || '-' }}
                                        </div>
                                    </div>
                                    <div class="text-right text-xs">
                                        <div>
                                            Sistem:
                                            {{ Number(item.current_stock) }}
                                            {{ item.uom?.name }}
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </AsyncSelectField>
                    </div>
                </div>

                <div v-if="form.errors.items" class="text-danger text-sm mb-2">
                    {{ form.errors.items }}
                </div>

                <div
                    v-if="form.items.length === 0"
                    class="text-center py-2 text-gray-500 border border-dashed rounded-lg"
                >
                    Belum ada item yang ditambahkan. Silakan cari item pada kolom pencarian di atas.
                </div>

                <div v-else class="space-y-2 max-h-96 overflow-y-auto pr-1">
                    <div
                        v-for="(item, index) in form.items"
                        :key="item.inventory_item_id || index"
                        class="p-2 border rounded-lg bg-white shadow-sm space-y-2"
                    >
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-semibold text-gray-800">
                                    {{ item.name }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    SKU: {{ item.sku || '-' }} | Satuan:
                                    {{ item.uom || '-' }} | Stok saat ini di outlet:
                                    <span class="font-medium text-gray-700">
                                        {{ item.current_stock ?? 0 }}
                                    </span>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="btn btn-highlight-danger btn-sm"
                                @click="removeItem(index)"
                                title="Hapus Item"
                            >
                                <FontAwesomeIcon :icon="faTrash" />
                            </button>
                        </div>

                        <div class="grid grid-cols-12 gap-2">
                            <div
                                :class="
                                    item.qty_change > 0
                                        ? 'col-span-12 md:col-span-6'
                                        : 'col-span-12'
                                "
                            >
                                <NumberField
                                    :id="'qty_' + index"
                                    v-model="item.qty_change"
                                    label="Perubahan Qty (+/-)"
                                    placeholder="Misal: -2 atau 5"
                                    step="any"
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
                                class="col-span-12 md:col-span-6"
                                v-if="item.qty_change > 0"
                            >
                                <NumberField
                                    :id="'unit_cost_' + index"
                                    v-model="item.unit_cost"
                                    label="HPP / Unit Cost (Opsional)"
                                    placeholder="Auto (Moving Avg)"
                                    min="0"
                                    step="any"
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
                Simpan Penyesuaian
            </button>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AsyncOutletDropdown from '@/Components/Form/AsyncOutletDropdown.vue';
import AsyncSelectField from '@/Components/Form/AsyncSelectField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';
import TextField from '@/Components/Form/TextField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faTrash } from '@fortawesome/free-solid-svg-icons';
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
});

const form = useForm({
    outlet_id: '',
    reason: '',
    notes: '',
    items: [],
});

const loadedOutlets = ref([]);

const onOutletsLoaded = (outlets) => {
    loadedOutlets.value = outlets;
    if (!form.outlet_id && outlets.length === 1) {
        form.outlet_id = outlets[0].id;
    }
};

const reasonOptions = [
    { label: 'Rusak / Terbuang (Waste)', value: 'waste' },
    { label: 'Kedaluwarsa (Expired)', value: 'expired' },
    { label: 'Hilang (Lost)', value: 'lost' },
    { label: 'Koreksi Salah Input (Correction)', value: 'correction' },
    { label: 'Produksi (Production)', value: 'production' },
    { label: 'Lainnya (Other)', value: 'other' },
];

const addItemFromSearch = (item) => {
    const exists = form.items.find((i) => i.inventory_item_id === item.id);
    if (!exists) {
        form.items.unshift({
            inventory_item_id: item.id,
            name: item.name,
            sku: item.sku || '-',
            uom: item.uom?.name || '-',
            current_stock: item.current_stock ?? 0,
            qty_change: '',
            unit_cost: '',
            description: '',
        });
    }
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

watch(
    () => form.outlet_id,
    (newVal, oldVal) => {
        if (oldVal && newVal !== oldVal) {
            form.items = [];
        }
    },
);

onMounted(() => {
    form.reset();
    form.clearErrors();
    form.items = [];
    if (loadedOutlets.value.length === 1) {
        form.outlet_id = loadedOutlets.value[0].id;
    }
});

const close = () => {
    form.reset();
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
