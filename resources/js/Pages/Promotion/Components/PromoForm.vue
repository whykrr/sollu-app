<template>
    <form class="space-y-4" @submit.prevent="submit">
        <!-- Section 1: Informasi Dasar -->
        <div class="space-y-2">
            <h3 class="text-sm font-semibold text-slate-700 uppercase">
                Informasi Dasar
            </h3>
            <TextField
                id="name"
                v-model="form.name"
                label="Nama Promo"
                :error="form.errors.name"
                required
            />
            <TextareaField
                id="description"
                v-model="form.description"
                label="Deskripsi"
                :error="form.errors.description"
            />
        </div>

        <!-- Section 2: Tipe & Target -->
        <div class="space-y-2 border-t pt-4">
            <h3 class="text-sm font-semibold text-slate-700 uppercase">
                Tipe & Target Diskon
            </h3>
            <div class="grid grid-cols-2 gap-2">
                <DropdownField
                    id="target_type"
                    v-model="form.target_type"
                    label="Target Diskon"
                    :options="targetTypeOptions"
                    :error="form.errors.target_type"
                />
                <DropdownField
                    id="promo_type"
                    v-model="form.promo_type"
                    label="Tipe Diskon"
                    :options="promoTypeOptions"
                    :error="form.errors.promo_type"
                />
            </div>

            <div class="grid grid-cols-2 gap-2">
                <NumberField
                    id="discount_value"
                    v-model="form.discount_value"
                    label="Nilai Diskon"
                    :error="form.errors.discount_value"
                    required
                />
                <NumberField
                    v-if="form.promo_type === 'percentage'"
                    id="max_discount"
                    v-model="form.max_discount"
                    label="Batas Maksimum Diskon (Rp)"
                    :error="form.errors.max_discount"
                />
            </div>
        </div>

        <!-- Section 3: Cakupan Produk -->
        <div
            v-if="form.target_type === 'product'"
            class="space-y-2 border-t pt-4"
        >
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-700 uppercase mb-0">
                    Cakupan Produk
                </h3>
                <button
                    v-if="selectedProducts.length > 0"
                    type="button"
                    class="text-xs text-primary-600 hover:text-primary-700 font-medium select-none"
                    @click="selectedProducts = []"
                >
                    Hapus Semua
                </button>
            </div>
            <AsyncSelectField
                id="product_search"
                label="Cari & Pilih Produk"
                :api-url="route('api.internal.inventory-items.search')"
                placeholder="Ketik nama produk..."
                :error="form.errors.inventory_item_ids"
                @select="addProduct"
            />

            <div v-if="selectedProducts.length > 0" class="mt-2 space-y-1">
                <div
                    v-for="product in selectedProducts"
                    :key="product.id"
                    class="flex items-center justify-between bg-slate-50 p-2 rounded border border-slate-200"
                >
                    <span class="text-sm">{{ product.name }}</span>
                    <button
                        type="button"
                        class="text-danger hover:text-red-700"
                        @click="removeProduct(product.id)"
                    >
                        <FontAwesomeIcon :icon="faTimes" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Section 4: Jadwal & Waktu -->
        <div class="space-y-2 border-t pt-4">
            <h3 class="text-sm font-semibold text-slate-700 uppercase">
                Jadwal & Waktu Operasional
            </h3>
            <div class="grid grid-cols-2 gap-2">
                <TextField
                    id="start_date"
                    v-model="form.start_date"
                    type="date"
                    label="Tanggal Mulai"
                    :error="form.errors.start_date"
                    required
                />
                <TextField
                    id="end_date"
                    v-model="form.end_date"
                    type="date"
                    label="Tanggal Berakhir"
                    :error="form.errors.end_date"
                    required
                />
            </div>
            <div class="grid grid-cols-2 gap-2">
                <TextField
                    id="start_time"
                    v-model="form.start_time"
                    type="time"
                    label="Jam Mulai (Opsional)"
                    :error="form.errors.start_time"
                />
                <TextField
                    id="end_time"
                    v-model="form.end_time"
                    type="time"
                    label="Jam Selesai (Opsional)"
                    :error="form.errors.end_time"
                />
            </div>
        </div>

        <!-- Section 5: Cakupan Outlet -->
        <div class="space-y-2 border-t border-slate-100 pt-4">
            <h3
                class="text-xs font-semibold text-slate-500 uppercase tracking-wider"
            >
                Cakupan Outlet
            </h3>
            <label
                class="flex items-center justify-between border border-slate-200 p-3 rounded-xl cursor-pointer hover:bg-slate-50/80 transition-all w-full select-none"
                :class="{
                    'border-primary-200 bg-primary-50/20':
                        form.applies_to_all_outlets,
                }"
            >
                <div>
                    <div class="font-semibold text-sm text-slate-800">
                        Semua outlet
                    </div>
                    <div class="text-xs text-slate-500">
                        Promo ini berlaku untuk semua outlet yang dimiliki.
                    </div>
                </div>
                <input
                    v-model="form.applies_to_all_outlets"
                    type="checkbox"
                    class="rounded h-4 w-4 text-primary focus:ring-primary-500 cursor-pointer"
                />
            </label>

            <div v-if="!form.applies_to_all_outlets" class="mt-2 space-y-2">
                <div
                    class="bg-slate-50/60 border border-slate-200 p-3 rounded-xl space-y-2"
                >
                    <SelectionGroupField
                        v-model="form.outlet_ids"
                        multiple
                        label="Pilih Outlet"
                        :options="outlets"
                        name="outlet_ids"
                        class="sm btn-sm"
                    />
                </div>
                <div
                    v-if="form.errors.outlet_ids"
                    class="text-danger text-xs select-none"
                >
                    {{ form.errors.outlet_ids }}
                </div>
            </div>
        </div>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-end gap-2 w-full">
                <button
                    type="button"
                    class="btn btn-flat"
                    @click="popUpStore.close"
                >
                    Batal
                </button>
                <button
                    type="submit"
                    class="btn btn-highlight-main"
                    :disabled="form.processing"
                    @click="submit"
                >
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </button>
            </div>
        </Teleport>
    </form>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePopUpStore } from '@/store/popup';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faTimes } from '@fortawesome/free-solid-svg-icons';
import TextField from '@/Components/Form/TextField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue';
import AsyncSelectField from '@/Components/Form/AsyncSelectField.vue';
import { useAuth } from '@/Composable/useAuth';

const props = defineProps({
    promo: {
        type: Object,
        default: null,
    },
});

const popUpStore = usePopUpStore();
const isMounted = ref(false);

const { outlets: userOutlets } = useAuth();

const outlets = computed(
    () =>
        userOutlets.value?.map((store) => ({
            value: store.id,
            label: store.name,
        })) || [],
);

const targetTypeOptions = [
    { value: 'product', label: 'Per Produk' },
    { value: 'bill', label: 'Per Bill' },
];

const promoTypeOptions = [
    { value: 'percentage', label: 'Persentase (%)' },
    { value: 'fixed', label: 'Nominal Tetap (Rp)' },
];

const selectedProducts = ref(props.promo?.inventory_items || []);

const form = useForm({
    name: props.promo?.name || '',
    description: props.promo?.description || '',
    target_type: props.promo?.target_type || 'bill',
    promo_type: props.promo?.promo_type || 'percentage',
    discount_value: props.promo?.discount_value || 0,
    max_discount: props.promo?.max_discount || null,
    start_date: props.promo?.start_date || '',
    end_date: props.promo?.end_date || '',
    start_time: props.promo?.start_time || '',
    end_time: props.promo?.end_time || '',
    applies_to_all_outlets: props.promo
        ? props.promo.applies_to_all_outlets
        : true,
    inventory_item_ids: [],
    outlet_ids: props.promo?.outlets?.map((o) => o.id) || [],
});

onMounted(() => {
    isMounted.value = true;
});

// Logic to clear max_discount when type is fixed
watch(
    () => form.promo_type,
    (newVal) => {
        if (newVal === 'fixed') {
            form.max_discount = null;
        }
    },
);

// Update inventory_item_ids form array when selectedProducts changes
watch(
    selectedProducts,
    (newVal) => {
        form.inventory_item_ids = newVal.map((p) => p.id);
    },
    { deep: true, immediate: true },
);

const addProduct = (product) => {
    if (!selectedProducts.value.find((p) => p.id === product.id)) {
        selectedProducts.value.push(product);
    }
};

const removeProduct = (id) => {
    selectedProducts.value = selectedProducts.value.filter((p) => p.id !== id);
};

const submit = () => {
    if (props.promo) {
        form.put(route('promotions.update', props.promo.id), {
            onSuccess: () => popUpStore.close(),
        });
    } else {
        form.post(route('promotions.store'), {
            onSuccess: () => popUpStore.close(),
        });
    }
};
</script>
