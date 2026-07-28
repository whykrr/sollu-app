<template>
    <PopUpPage
        :title="title"
        :sub-title="'#' + subTitle"
        :class="{ show: show }"
        size="lg"
        @close="closeDetail"
    >
        <div class="p-4 mb-4 bg-slate-100 rounded-lg">
            <div v-if="loading" class="text-center text-gray-500 py-4">
                Memuat data produk...
            </div>
            <div
                v-else-if="headerData"
                class="grid grid-cols-2 md:grid-cols-4 gap-4"
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
        </div>

        <div class="">
            <Tab
                v-if="!loading && headerData"
                :pages="tabPages"
                :vertical="false"
            />
        </div>
    </PopUpPage>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import PopUpPage from '@/Components/UI/PopUpPage.vue';
import Tab from '@/Components/UI/Tab.vue';
import axios from 'axios';

// Tabs Components
import SummaryTab from '../Tabs/SummaryTab.vue';
import MovementTab from '../Tabs/MovementTab.vue';
import ChartTab from '../Tabs/ChartTab.vue';

// Icons
import {
    faBuilding,
    faHistory,
    faChartLine,
} from '@fortawesome/free-solid-svg-icons';

const emit = defineEmits(['close']);

const props = defineProps({
    show: Boolean,
    item: Object,
});

const title = computed(() =>
    props.item ? props.item.item_name : 'Detail Stok',
);
const subTitle = computed(() => (props.item ? props.item.sku : ''));

const loading = ref(false);
const headerData = ref(null);
const balancesData = ref([]);

watch(
    () => props.show,
    (newVal) => {
        if (newVal && props.item) {
            fetchHeaderData();
        } else {
            headerData.value = null;
            balancesData.value = [];
        }
    },
);

const fetchHeaderData = async () => {
    loading.value = true;
    try {
        const response = await axios.get(
            route('inventories.stocks.show', props.item.id),
        );
        headerData.value = response.data.item;
        balancesData.value = response.data.balances;
    } catch (error) {
        console.error('Failed to load header data', error);
    } finally {
        loading.value = false;
    }
};

const closeDetail = () => {
    emit('close');
};

const tabPages = computed(() => {
    if (!headerData.value) return [];
    return [
        {
            label: 'Ringkasan',
            icon: faBuilding,
            page: SummaryTab,
            props: { item: props.item, balances: balancesData.value },
        },
        {
            label: 'Riwayat',
            icon: faHistory,
            page: MovementTab,
            props: { item: props.item },
        },
        {
            label: 'Grafik',
            icon: faChartLine,
            page: ChartTab,
            props: { item: props.item },
        },
    ];
});
</script>
