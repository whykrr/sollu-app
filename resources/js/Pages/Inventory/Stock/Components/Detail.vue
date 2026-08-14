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

            <!-- SKU, Barcode and Outlet Row -->
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

                <div class="flex items-center gap-6">
                    <!-- SKU Section -->
                    <div class="flex flex-col items-end">
                        <div class="text-xs text-gray-500 uppercase mb-1 flex items-center gap-2">
                            SKU
                            <button
                                class="text-main hover:underline text-[10px]"
                                @click="openSkuModal"
                            >
                                {{ headerData?.sku ? 'Ubah' : 'Tambah' }}
                            </button>
                        </div>
                        <div class="font-mono text-sm font-semibold text-slate-800">
                            {{ headerData?.sku || '-' }}
                        </div>
                    </div>

                    <!-- Barcode Section -->
                    <div class="flex flex-col items-end">
                        <div
                            class="text-xs text-gray-500 uppercase mb-1 flex items-center gap-2"
                        >
                            Barcode
                            <button
                                class="text-main hover:underline text-[10px]"
                                @click="openBarcodeModal"
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
                    class="btn btn-sm btn-outline-main"
                    @click="openInitialStockModal"
                >
                    Input Stok Awal
                </button>
                <button
                    v-if="!loading && movementsData.length > 0"
                    class="btn btn-sm btn-outline-secondary"
                    @click="exportPdf"
                >
                    Ekspor PDF Riwayat
                </button>
            </div>
        </div>

        <div>
            <Tab
                v-if="!loading && headerData"
                :pages="tabPages"
                :vertical="false"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, markRaw } from 'vue';
import Tab from '@/Components/UI/Tab.vue';
import axios from 'axios';
import JsBarcode from 'jsbarcode';
import { useModalStore } from '@/store/notification';

// Modals
import BarcodeFormModal from './Modals/BarcodeFormModal.vue';
import SkuFormModal from './Modals/SkuFormModal.vue';
import InitialStockFormModal from './Modals/InitialStockFormModal.vue';

// Tabs Components
import MovementTab from '../Tabs/MovementTab.vue';
import ChartTab from '../Tabs/ChartTab.vue';

// Icons
import { faHistory, faChartLine } from '@fortawesome/free-solid-svg-icons';

const emit = defineEmits(['close']);

const props = defineProps({
    item: Object,
});

const modalStore = useModalStore();
const barcodeRef = ref(null);

const loading = ref(false);
const headerData = ref(null);
const currentBalanceData = ref(null);
const movementsData = ref([]);
const chartData = ref(null);

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
    modalStore.open({
        title: 'Ubah Barcode',
        component: markRaw(BarcodeFormModal),
        props: {
            stockId: props.item.id,
            initialBarcode: headerData.value?.barcode || '',
        },
        showFooter: false,
        onConfirm: () => fetchHeaderData(),
    });
};

const openSkuModal = () => {
    modalStore.open({
        title: 'Ubah SKU',
        component: markRaw(SkuFormModal),
        props: {
            stockId: props.item.id,
            initialSku: headerData.value?.sku || '',
        },
        showFooter: false,
        onConfirm: () => fetchHeaderData(),
    });
};

const openInitialStockModal = () => {
    modalStore.open({
        title: 'Input Stok Awal',
        component: markRaw(InitialStockFormModal),
        props: {
            stockId: props.item.id,
        },
        showFooter: false,
        onConfirm: () => fetchHeaderData(),
    });
};

const exportPdf = () => {
    window.open(
        route('inventories.stocks.export.pdf', props.item.id),
        '_blank',
    );
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
