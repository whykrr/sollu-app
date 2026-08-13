<template>
    <div>
        <form @submit.prevent="confirmSubmit('submit')" class="space-y-2">
            <div
                v-if="opname"
                class="mb-4 bg-gray-50 p-4 rounded-lg flex justify-between"
            >
                <div>
                    <p>
                        <strong>Nomor Opname:</strong>
                        {{ opname.opname_number }}
                    </p>
                    <p><strong>Status:</strong> Sedang Berjalan</p>
                </div>
            </div>

            <div v-if="!opname" class="grid grid-cols-1 gap-2">
                <AsyncOutletDropdown
                    id="outlet_id"
                    v-model="form.outlet_id"
                    label="Pilih Outlet"
                    placeholder="-- Pilih Outlet --"
                    :class="{ 'is-invalid': form.errors.outlet_id }"
                    :error="form.errors.outlet_id"
                    @loaded="onOutletsLoaded"
                />
            </div>

            <TextareaField
                id="notes"
                v-model="form.notes"
                label="Catatan (Opsional)"
                :class="{ 'is-invalid': form.errors.notes }"
                :error="form.errors.notes"
            />

            <div class="mt-3 border-t pt-2">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-semibold">Pencatatan Fisik Stok</h3>
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
                                    outlet_id: opname
                                        ? opname.outlet_id
                                        : form.outlet_id,
                                }"
                                :min-chars="3"
                                :disabled="!form.outlet_id && !opname"
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
                        <button
                            type="button"
                            class="btn btn-outline-main btn-sm"
                            @click="loadAllItems(false)"
                            :disabled="
                                (!form.outlet_id && !opname) || isLoadingItems
                            "
                        >
                            {{
                                isLoadingItems
                                    ? 'Memuat...'
                                    : 'Muat Item (Parsial)'
                            }}
                        </button>
                    </div>
                </div>

                <div
                    v-if="form.items.length === 0"
                    class="text-center py-6 text-gray-500 border border-dashed rounded-lg"
                >
                    Belum ada item yang ditambahkan. Silakan cari atau muat
                    semua item.
                </div>

                <div v-else class="space-y-2 max-h-96 overflow-y-auto">
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
                                Stok Sistem
                            </div>
                            <div
                                class="text-center font-semibold bg-gray-100 py-1 rounded"
                            >
                                {{ item.system_qty }}
                            </div>
                        </div>
                        <div class="w-32">
                            <NumberField
                                v-model="item.actual_qty"
                                type="number"
                                label="Stok Fisik"
                                min="0"
                                step="any"
                                :class="{
                                    'is-invalid':
                                        form.errors[
                                            `items.${index}.actual_qty`
                                        ],
                                }"
                            />
                        </div>
                        <div class="w-32 text-center">
                            <div class="text-xs text-gray-500">Selisih</div>
                            <div
                                class="font-bold text-lg"
                                :class="
                                    differenceColor(
                                        item.actual_qty,
                                        item.system_qty,
                                    )
                                "
                            >
                                {{
                                    formatDifference(
                                        item.actual_qty,
                                        item.system_qty,
                                    )
                                }}
                            </div>
                        </div>
                        <button
                            type="button"
                            class="btn btn-highlight-danger"
                            @click="removeItem(index)"
                        >
                            <FontAwesomeIcon :icon="faTrash"></FontAwesomeIcon>
                        </button>
                    </div>
                </div>

                <div v-if="hasMoreItems" class="text-center mt-3">
                    <button
                        type="button"
                        class="btn btn-outline-main btn-sm"
                        @click="loadAllItems(true)"
                        :disabled="isLoadingItems"
                    >
                        {{ isLoadingItems ? 'Memuat...' : 'Muat Lebih Banyak' }}
                    </button>
                </div>

                <div v-if="form.errors.items" class="text-danger text-sm mt-2">
                    {{ form.errors.items }}
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
                v-if="!opname"
                type="button"
                class="btn btn-main"
                @click="confirmSubmit('save')"
                :disabled="form.processing || form.items.length === 0"
            >
                Mulai Opname
            </button>
            <button
                v-if="opname"
                type="button"
                class="btn btn-info"
                @click="confirmSubmit('submit')"
                :disabled="form.processing || form.items.length === 0"
            >
                Ajukan Persetujuan
            </button>
        </Teleport>
    </div>
    <Modal
        :class="{ show: showConfirm, hide: !showConfirm }"
        :title="confirmTitle"
        @close="showConfirm = false"
    >
        <p class="text-gray-600">{{ confirmMessage }}</p>
        <template #footer>
            <button class="btn btn-flat" @click="showConfirm = false">
                Batal
            </button>
            <button
                class="btn"
                :class="
                    confirmActionType === 'submit' ? 'btn-info' : 'btn-main'
                "
                @click="executeSubmit"
            >
                Ya, Lanjutkan
            </button>
        </template>
    </Modal>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import TextareaField from '@/Components/Form/TextareaField.vue';
import AsyncOutletDropdown from '@/Components/Form/AsyncOutletDropdown.vue';
import AsyncSelectField from '@/Components/Form/AsyncSelectField.vue';
import Modal from '@/Components/Notifications/Modal.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faTrash } from '@fortawesome/free-solid-svg-icons';
import NumberField from '@/Components/Form/NumberField.vue';

const props = defineProps({
    opname: Object,
});

const emit = defineEmits(['close']);
const isMounted = ref(false);

const form = useForm({
    outlet_id: '',
    notes: '',
    items: [],
});

const loadedOutlets = ref([]);
const currentPage = ref(1);
const hasMoreItems = ref(false);
const isLoadingItems = ref(false);

const onOutletsLoaded = (outlets) => {
    loadedOutlets.value = outlets;
    if (!props.opname && !form.outlet_id && outlets.length === 1) {
        form.outlet_id = outlets[0].id;
    }
};

const showConfirm = ref(false);
const confirmTitle = ref('');
const confirmMessage = ref('');
const confirmActionType = ref('');

onMounted(() => {
    isMounted.value = true;
    currentPage.value = 1;
    hasMoreItems.value = false;

    if (props.opname) {
        form.outlet_id = props.opname.outlet_id;
        form.notes = props.opname.notes || '';
        form.items = props.opname.items.map((i) => ({
            inventory_item_id: i.inventory_item_id,
            name: i.inventory_item?.name,
            sku: i.inventory_item?.sku || '-',
            uom: i.inventory_item?.uom?.name || '-',
            system_qty: i.system_qty_formatted,
            actual_qty: i.actual_qty_formatted,
        }));
    } else {
        form.reset();
        form.items = [];
        if (loadedOutlets.value.length === 1) {
            form.outlet_id = loadedOutlets.value[0].id;
        }
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
            actual_qty: item.current_stock || 0,
        });
    }
};

const loadAllItems = async (isLoadMore = false) => {
    const outletId = props.opname ? props.opname.outlet_id : form.outlet_id;
    if (!outletId) {
        alert('Pilih outlet terlebih dahulu!');
        return;
    }

    if (!isLoadMore) {
        currentPage.value = 1;
    }

    isLoadingItems.value = true;
    try {
        const response = await axios.get(
            route('api.internal.inventory-items.partial'),
            {
                params: {
                    outlet_id: outletId,
                    page: currentPage.value,
                    limit: 50,
                },
            },
        );

        const newItems = response.data.data;
        const meta = response.data.meta;

        newItems.forEach((i) => {
            if (
                !form.items.find(
                    (existing) => existing.inventory_item_id === i.id,
                )
            ) {
                form.items.push({
                    inventory_item_id: i.id,
                    name: i.name,
                    sku: i.sku || '-',
                    uom: i.uom?.name || '-',
                    system_qty: i.current_stock || 0,
                    actual_qty: i.current_stock || 0,
                });
            }
        });

        hasMoreItems.value = meta.current_page < meta.last_page;
        if (hasMoreItems.value) {
            currentPage.value++;
        }
    } catch (error) {
        console.error('Error loading items:', error);
        alert('Gagal memuat item. Pastikan API berfungsi dengan baik.');
    } finally {
        isLoadingItems.value = false;
    }
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const differenceColor = (actual, system) => {
    const diff = Number(actual || 0) - Number(system || 0);
    if (diff > 0) return 'text-success';
    if (diff < 0) return 'text-danger';
    return 'text-gray-400';
};

const formatDifference = (actual, system) => {
    const diff = Number(actual || 0) - Number(system || 0);
    const formatted = new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 2,
    }).format(Math.abs(diff));
    if (diff > 0) return '+' + formatted;
    if (diff < 0) return '-' + formatted;
    return '0';
};

const close = () => {
    form.clearErrors();
    form.reset();
    emit('close');
};

const confirmSubmit = (type) => {
    confirmActionType.value = type;
    if (type === 'save') {
        confirmTitle.value = 'Mulai Opname';
        confirmMessage.value =
            'Sesi opname baru akan dibuat. Pastikan outlet sudah dibekukan jika diperlukan. Lanjutkan?';
    } else {
        confirmTitle.value = 'Ajukan Persetujuan';
        confirmMessage.value =
            'Apakah Anda yakin ingin mengajukan hasil opname ini untuk disetujui? Data tidak bisa diubah lagi setelah diajukan.';
    }
    showConfirm.value = true;
};

const executeSubmit = () => {
    showConfirm.value = false;

    const options = {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => close(),
    };

    if (props.opname) {
        form.put(route('inventory.opnames.update', props.opname.id), options);
    } else {
        form.post(route('inventory.opnames.store'), options);
    }
};
</script>
