<template>
    <div>
        <form class="space-y-2" @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <div
                    class="bg-slate-50/60 border border-slate-200 p-3 rounded-xl space-y-2"
                >
                    <SelectionGroupField
                        id="from_outlet_id"
                        v-model="form.from_outlet_id"
                        label="Dari Outlet"
                        :options="outletOptions"
                        :error="form.errors.from_outlet_id"
                        :disabled="
                            form.items.length > 0 && form.from_outlet_id !== ''
                        "
                        name="from_outlet_id"
                        class="sm btn-sm"
                    />
                </div>
                <div
                    class="bg-slate-50/60 border border-slate-200 p-3 rounded-xl space-y-2"
                >
                    <SelectionGroupField
                        id="to_outlet_id"
                        v-model="form.to_outlet_id"
                        label="Ke Outlet"
                        :options="toOutletOptions"
                        :error="form.errors.to_outlet_id"
                        name="to_outlet_id"
                        class="sm btn-sm"
                    />
                </div>
            </div>

            <TextareaField
                id="notes"
                v-model="form.notes"
                label="Catatan Transfer"
                :class="{ 'is-invalid': form.errors.notes }"
                :error="form.errors.notes"
            />

            <div class="mt-2 border-t pt-2">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-semibold">Item yang Ditransfer</h3>
                    <div class="flex gap-2 items-end">
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
                                    outlet_id: form.from_outlet_id,
                                }"
                                :min-chars="3"
                                :disabled="!form.from_outlet_id"
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
                </div>

                <div
                    v-if="!form.from_outlet_id"
                    class="text-center py-6 text-gray-500 border border-dashed rounded-lg"
                >
                    Silakan pilih "Dari Outlet" terlebih dahulu untuk mencari
                    atau memuat stok item.
                </div>
                <div
                    v-else-if="form.items.length === 0"
                    class="text-center py-6 text-gray-500 border border-dashed rounded-lg"
                >
                    Belum ada item ditambahkan. Silakan cari atau muat item.
                </div>

                <div v-else class="space-y-2 max-h-96 overflow-y-auto mt-2">
                    <div
                        v-for="(item, index) in form.items"
                        :key="item.inventory_item_id"
                        class="flex gap-2 items-center border p-2 rounded-lg bg-white"
                    >
                        <div class="w-8 text-center text-gray-500 font-bold">
                            {{ index + 1 }}
                        </div>
                        <div class="flex-1">
                            <div class="font-semibold">{{ item.name }}</div>
                            <div class="text-sm text-gray-500">
                                SKU: {{ item.sku }} | Satuan: {{ item.uom }}
                            </div>
                        </div>
                        <div class="w-32">
                            <div class="text-xs text-gray-500 text-center">
                                Stok Saat Ini
                            </div>
                            <div
                                class="text-center font-semibold bg-gray-100 py-1 rounded"
                            >
                                {{ item.system_qty }}
                            </div>
                        </div>
                        <div class="w-40">
                            <NumberField
                                v-model="item.qty"
                                type="number"
                                label="Kuantitas Transfer"
                                min="0.01"
                                step="any"
                                :class="{
                                    'is-invalid':
                                        form.errors[`items.${index}.qty`],
                                }"
                                :error="form.errors[`items.${index}.qty`]"
                            />
                        </div>
                        <button
                            type="button"
                            class="btn btn-highlight-danger"
                            title="Hapus"
                            @click="removeItem(index)"
                        >
                            <FontAwesomeIcon :icon="faTrash"></FontAwesomeIcon>
                        </button>
                    </div>
                </div>

                <div v-if="form.errors.items" class="text-danger text-sm mt-2">
                    {{ form.errors.items }}
                </div>
            </div>
        </form>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-between w-full">
                <div class="text-gray-500 pt-2 font-medium">
                    Total Item: {{ form.items.length }}
                </div>
                <div class="flex gap-2">
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
                        :disabled="form.processing || form.items.length === 0"
                        @click="submit"
                    >
                        {{ isEdit ? 'Simpan Perubahan' : 'Simpan Permintaan' }}
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { usePopUpStore } from '@/store/popup';
import NumberField from '@/Components/Form/NumberField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';
import AsyncSelectField from '@/Components/Form/AsyncSelectField.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faTrash } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    outlets: Array,
    transferData: Object, // For editing
});

const emit = defineEmits(['refresh']);
const popUpStore = usePopUpStore();
const isMounted = ref(false);

const form = useForm({
    from_outlet_id: '',
    to_outlet_id: '',
    notes: '',
    items: [],
});

const isEdit = computed(() => !!props.transferData);

const outletOptions = computed(() => {
    return (props.outlets || []).map((o) => ({
        label: o.is_stock_frozen ? `${o.name} (Dibekukan)` : o.name,
        value: o.id,
        disabled: o.is_stock_frozen,
    }));
});

const toOutletOptions = computed(() => {
    return (props.outlets || [])
        .filter((o) => o.id !== form.from_outlet_id)
        .map((o) => ({
            label: o.is_stock_frozen ? `${o.name} (Dibekukan)` : o.name,
            value: o.id,
            disabled: o.is_stock_frozen,
        }));
});

watch(
    () => form.from_outlet_id,
    (newVal) => {
        if (!newVal) {
            form.items = [];
        }
    },
);

onMounted(() => {
    isMounted.value = true;
    form.reset();
    form.clearErrors();

    if (props.transferData) {
        form.from_outlet_id = props.transferData.from_outlet_id;
        form.to_outlet_id = props.transferData.to_outlet_id;
        form.notes = props.transferData.notes;
        form.items = props.transferData.items.map((i) => ({
            inventory_item_id: i.inventory_item_id,
            name: i.inventory_item?.name || '-',
            sku: i.inventory_item?.sku || '-',
            uom: i.inventory_item?.uom?.name || '-',
            system_qty: i.current_stock || 0, // Ideally fetched from backend, but fallback to 0
            qty: i.qty,
        }));
    }
});

const addItemFromSearch = (item) => {
    const exists = form.items.find((i) => i.inventory_item_id === item.id);
    if (!exists) {
        form.items.unshift({
            inventory_item_id: item.id,
            name: item.name,
            sku: item.sku || '-',
            uom: item.uom?.name || '-',
            system_qty: item.current_stock || 0,
            qty: 1, // Default qty to transfer
        });
    }
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const close = () => {
    form.clearErrors();
    popUpStore.close();
};

const submit = () => {
    if (isEdit.value) {
        form.put(route('inventory.transfers.update', props.transferData.id), {
            preserveScroll: true,
            onSuccess: () => {
                close();
                emit('refresh');
            },
        });
    } else {
        form.post(route('inventory.transfers.store'), {
            preserveScroll: true,
            onSuccess: () => {
                close();
                emit('refresh');
            },
        });
    }
};
</script>
