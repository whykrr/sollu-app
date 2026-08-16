<template>
    <div class="flex flex-col gap-4 p-4">
        <!-- Financial -->
        <div>
            <h3 class="text-base font-semibold text-slate-800 border-b pb-2 mb-3">Pengaturan Finansial</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <div class="col-span-1">
                    <NumberField
                        id="tax"
                        v-model="form.financial_tax"
                        label="Pajak (%)"
                        placeholder="10"
                    />
                </div>
                <div class="col-span-1">
                    <NumberField
                        id="service_fee"
                        v-model="form.financial_service_fee"
                        label="Service Fee (%)"
                        placeholder="5"
                    />
                </div>
            </div>
        </div>

        <!-- POS -->
        <div>
            <h3 class="text-base font-semibold text-slate-800 border-b pb-2 mb-3">Pengaturan POS</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <div class="col-span-1 flex items-center justify-between p-3 border rounded-lg">
                    <div>
                        <div class="font-medium text-slate-700">Auto Print Struk</div>
                        <div class="text-xs text-slate-500">Cetak struk otomatis setelah bayar</div>
                    </div>
                    <Switch id="pos_auto_print" v-model="form.pos_auto_print" size="lg" />
                </div>
                <div class="col-span-1 flex items-center justify-between p-3 border rounded-lg">
                    <div>
                        <div class="font-medium text-slate-700">Kitchen Display</div>
                        <div class="text-xs text-slate-500">Gunakan layar dapur (KDS)</div>
                    </div>
                    <Switch id="pos_kitchen_display" v-model="form.pos_kitchen_display" size="lg" />
                </div>
                <div class="col-span-1 md:col-span-2">
                    <DropdownField
                        id="receipt_format"
                        v-model="form.pos_receipt_format"
                        label="Format Struk"
                        placeholder="Pilih format struk"
                        :options="receiptFormats"
                    />
                </div>
            </div>
        </div>

        <!-- Inventory -->
        <div>
            <h3 class="text-base font-semibold text-slate-800 border-b pb-2 mb-3">Pengaturan Inventori</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <div class="col-span-1 flex items-center justify-between p-3 border rounded-lg">
                    <div>
                        <div class="font-medium text-slate-700">Stock Tracking</div>
                        <div class="text-xs text-slate-500">Pantau stok produk</div>
                    </div>
                    <Switch id="inv_stock_tracking" v-model="form.inventory_stock_tracking" size="lg" />
                </div>
                <div class="col-span-1 flex items-center justify-between p-3 border rounded-lg">
                    <div>
                        <div class="font-medium text-slate-700">Boleh Stok Minus</div>
                        <div class="text-xs text-slate-500">Izinkan penjualan jika stok kosong</div>
                    </div>
                    <Switch id="inv_negative_stock" v-model="form.inventory_negative_stock" size="lg" />
                </div>
            </div>
        </div>

        <div class="flex justify-end mt-4 pt-4 border-t border-slate-100">
            <button
                class="btn btn-main px-6 py-2 rounded-lg shadow-sm font-medium"
                :disabled="form.processing"
                @click="submitForm"
            >
                Simpan Pengaturan
            </button>
        </div>
    </div>
</template>

<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import NumberField from '@/Components/Form/NumberField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import Switch from '@/Components/Form/Switch.vue';

const props = defineProps({
    outlet: Object,
});

const form = useForm({
    financial_tax: '',
    financial_service_fee: '',
    pos_auto_print: 0,
    pos_kitchen_display: 0,
    pos_receipt_format: '',
    inventory_stock_tracking: 1,
    inventory_negative_stock: 0,
});

const receiptFormats = [
    { value: 'standard', label: 'Standar (58mm)' },
    { value: 'large', label: 'Besar (80mm)' },
];

watch(
    () => props.outlet,
    (outlet) => {
        if (outlet && outlet.settings) {
            outlet.settings.forEach(setting => {
                const mapKey = `${setting.category}_${setting.key}`;
                if (form[mapKey] !== undefined) {
                    if (['pos_auto_print', 'pos_kitchen_display', 'inventory_stock_tracking', 'inventory_negative_stock'].includes(mapKey)) {
                        form[mapKey] = setting.value == '1' || setting.value === true ? 1 : 0;
                    } else {
                        form[mapKey] = setting.value;
                    }
                }
            });
        }
    },
    { immediate: true },
);

const submitForm = () => {
    if (!props.outlet) return;

    const settingsArray = [
        { category: 'financial', key: 'tax', value: form.financial_tax },
        { category: 'financial', key: 'service_fee', value: form.financial_service_fee },
        { category: 'pos', key: 'auto_print', value: form.pos_auto_print },
        { category: 'pos', key: 'kitchen_display', value: form.pos_kitchen_display },
        { category: 'pos', key: 'receipt_format', value: form.pos_receipt_format },
        { category: 'inventory', key: 'stock_tracking', value: form.inventory_stock_tracking },
        { category: 'inventory', key: 'negative_stock', value: form.inventory_negative_stock },
    ];

    form.transform(() => ({ settings: settingsArray })).put(
        route('settings.outlets.settings.update', { outlet: props.outlet.id }),
        {
            preserveScroll: true,
            preserveState: true,
        }
    );
};
</script>
