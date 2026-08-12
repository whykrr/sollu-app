<template>
    <div>
        <div class="p-4 mb-4 bg-slate-100 rounded-lg">
            <div v-if="loading" class="text-center text-gray-500 py-4">
                Memuat data produk...
            </div>
            <div
                v-else-if="headerData"
                class="grid grid-cols-2 md:grid-cols-4 gap-2"
            >
                <div>
                    <div class="text-xs text-gray-500 uppercase">Kategori</div>
                    <div class="font-medium">
                        {{ headerData.product?.category?.name || '-' }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Tipe</div>
                    <div class="font-medium">
                        {{
                            headerData.item_type === 'raw_material'
                                ? 'Bahan Baku'
                                : 'Produk'
                        }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Satuan</div>
                    <div class="font-medium">
                        {{ headerData.uom?.name || '-' }}
                    </div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Min Stok</div>
                    <div class="font-medium">
                        {{ headerData.minimum_stock_formatted }}
                    </div>
                </div>
            </div>

            <!-- Barcode and Outlet Row -->
            <div
                v-if="headerData"
                class="mt-4 pt-4 border-t border-gray-200 flex justify-between items-center"
            >
                <div>
                    <div class="text-xs text-gray-500 uppercase">Outlet</div>
                    <div class="font-semibold text-main">
                        {{ item.outlet_name || '-' }}
                    </div>
                </div>

                <div class="flex flex-col items-end">
                    <div
                        class="text-xs text-gray-500 uppercase mb-1 flex items-center gap-2"
                    >
                        Barcode
                        <button
                            @click="openBarcodeModal"
                            class="text-main hover:underline text-[10px]"
                        >
                            {{ headerData?.barcode ? 'Ubah' : 'Tambah' }}
                        </button>
                    </div>
                    <div
                        v-if="headerData?.barcode"
                        class="bg-white p-2 rounded border"
                    >
                        <svg ref="barcodeRef"></svg>
                        <div
                            class="text-center text-xs font-mono tracking-widest mt-1"
                        >
                            {{ headerData.barcode }}
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-400 italic py-2">
                        Belum ada barcode
                    </div>
                </div>
            </div>

            <div
                class="mt-4 pt-4 border-t border-gray-200 flex justify-end gap-2"
            >
                <button
                    v-if="
                        !loading &&
                        currentBalanceData &&
                        currentBalanceData.current_stock == 0 &&
                        movementsData.length === 0
                    "
                    @click="openInitialStockModal"
                    class="btn btn-sm btn-outline-main"
                >
                    Input Stok Awal
                </button>
                <button
                    v-if="!loading && movementsData.length > 0"
                    @click="exportPdf"
                    class="btn btn-sm btn-outline-secondary"
                >
                    Ekspor PDF Riwayat
                </button>
            </div>
        </div>

        <div class="">
            <Tab
                v-if="!loading && headerData"
                :pages="tabPages"
                :vertical="false"
            />
        </div>

        <!-- Barcode Modal -->
    </div>
    <Modal
        :class="{ show: showBarcodeModal }"
        title="Ubah Barcode"
        @close="showBarcodeModal = false"
    >
        <div class="space-y-2">
            <div>
                <label class="block text-sm font-medium text-gray-700"
                    >Barcode (Scan/Ketik)</label
                >
                <input
                    type="text"
                    v-model="barcodeInput"
                    class="form block"
                    placeholder="Arahkan kursor kesini dan scan..."
                    @keyup.enter="saveBarcode"
                    autofocus
                />
            </div>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <button
                    class="btn btn-outline-secondary"
                    @click="showBarcodeModal = false"
                >
                    Batal
                </button>
                <button
                    class="btn btn-main"
                    :disabled="savingBarcode"
                    @click="saveBarcode"
                >
                    {{ savingBarcode ? 'Menyimpan...' : 'Simpan' }}
                </button>
            </div>
        </template>
    </Modal>

    <!-- Initial Stock Modal -->
    <Modal
        :class="{ show: showInitialStockModal }"
        title="Input Stok Awal"
        @close="showInitialStockModal = false"
    >
        <div class="space-y-2">
            <div class="bg-blue-50 text-blue-800 p-3 rounded text-sm">
                Fitur ini hanya digunakan untuk menginput stok pertama kali
                untuk barang yang belum memiliki riwayat mutasi sama sekali.
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700"
                    >Kuantitas (Qty)</label
                >
                <input
                    type="number"
                    step="0.01"
                    v-model="initialStockForm.qty"
                    class="form block"
                    placeholder="0"
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700"
                    >Harga Beli / HPP</label
                >
                <input
                    type="number"
                    step="0.01"
                    v-model="initialStockForm.purchase_price"
                    class="form block"
                    placeholder="0"
                />
            </div>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <button
                    class="btn btn-outline-secondary"
                    @click="showInitialStockModal = false"
                >
                    Batal
                </button>
                <button
                    class="btn btn-main"
                    :disabled="savingInitialStock"
                    @click="saveInitialStock"
                >
                    {{
                        savingInitialStock ? 'Menyimpan...' : 'Simpan Stok Awal'
                    }}
                </button>
            </div>
        </template>
    </Modal>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import Tab from '@/Components/UI/Tab.vue';
import Modal from '@/Components/Notifications/Modal.vue';
import axios from 'axios';
import JsBarcode from 'jsbarcode';

// Tabs Components
import MovementTab from '../Tabs/MovementTab.vue';
import ChartTab from '../Tabs/ChartTab.vue';

// Icons
import { faHistory, faChartLine } from '@fortawesome/free-solid-svg-icons';

const emit = defineEmits(['close']);

const props = defineProps({
    item: Object,
});

const title = computed(() =>
    props.item ? props.item.item_name : 'Detail Stok',
);
const subTitle = computed(() => (props.item ? props.item.sku : ''));

const barcodeRef = ref(null);

const loading = ref(false);
const headerData = ref(null);
const currentBalanceData = ref(null);
const movementsData = ref([]);
const chartData = ref(null);

const showBarcodeModal = ref(false);
const barcodeInput = ref('');
const savingBarcode = ref(false);

const showInitialStockModal = ref(false);
const initialStockForm = ref({ qty: '', purchase_price: '' });
const savingInitialStock = ref(false);

onMounted(() => {
    if (props.item) {
        fetchHeaderData();
    }
});

const fetchHeaderData = async () => {
    loading.value = true;
    try {
        const response = await axios.get(
            route('inventories.stocks.show', props.item.id),
        );
        headerData.value = response.data.item;
        currentBalanceData.value = response.data.current_balance;
        movementsData.value = response.data.movements;
        chartData.value = response.data.chart;

        if (headerData.value && headerData.value.barcode) {
            nextTick(() => {
                if (barcodeRef.value) {
                    JsBarcode(barcodeRef.value, headerData.value.barcode, {
                        format: 'CODE128',
                        width: 1.5,
                        height: 40,
                        displayValue: false,
                        margin: 0,
                    });
                }
            });
        }
    } catch (error) {
        console.error('Failed to load header data', error);
    } finally {
        loading.value = false;
    }
};

const openBarcodeModal = () => {
    barcodeInput.value = headerData.value?.barcode || '';
    showBarcodeModal.value = true;
};

const saveBarcode = async () => {
    if (!barcodeInput.value) return;
    savingBarcode.value = true;
    try {
        await axios.patch(
            route('inventories.stocks.barcode.update', props.item.id),
            {
                barcode: barcodeInput.value,
            },
        );
        showBarcodeModal.value = false;
        fetchHeaderData();
    } catch (error) {
        console.error(error);
        alert(error.response?.data?.message || 'Gagal menyimpan barcode');
    } finally {
        savingBarcode.value = false;
    }
};

const openInitialStockModal = () => {
    initialStockForm.value = { qty: '', purchase_price: '' };
    showInitialStockModal.value = true;
};

const saveInitialStock = async () => {
    if (!initialStockForm.value.qty || !initialStockForm.value.purchase_price)
        return;
    savingInitialStock.value = true;
    try {
        await axios.post(
            route('inventories.stocks.initial-stock.store', props.item.id),
            {
                qty: initialStockForm.value.qty,
                purchase_price: initialStockForm.value.purchase_price,
            },
        );
        showInitialStockModal.value = false;
        fetchHeaderData();
    } catch (error) {
        console.error(error);
        alert(error.response?.data?.message || 'Gagal menyimpan stok awal');
    } finally {
        savingInitialStock.value = false;
    }
};

const exportPdf = () => {
    window.open(
        route('inventories.stocks.export.pdf', props.item.id),
        '_blank',
    );
};

const closeDetail = () => {
    emit('close');
};

const tabPages = computed(() => {
    if (!headerData.value) return [];
    return [
        {
            label: 'Riwayat',
            icon: faHistory,
            page: MovementTab,
            props: { item: props.item, movements: movementsData.value },
        },
        {
            label: 'Grafik',
            icon: faChartLine,
            page: ChartTab,
            props: { item: props.item, chart: chartData.value },
        },
    ];
});
</script>
