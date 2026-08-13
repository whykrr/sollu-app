<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Buat Faktur Baru (B2B)" />
        </template>

        <div class="p-4 space-y-6">
            <!-- Header Info -->
            <div
                class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-lg border border-gray-100"
            >
                <div
                    class="bg-slate-50/60 border border-slate-200 p-3 rounded-xl space-y-2"
                >
                    <SelectionGroupField
                        v-model="form.outlet_id"
                        label="Pilih Outlet"
                        :options="
                            outlets.map((o) => ({ value: o.id, label: o.name }))
                        "
                        :error="form.errors.outlet_id"
                        name="outlet_id"
                        class="sm btn-sm"
                    />
                </div>
                <DropdownField
                    v-model="form.customer_id"
                    label="Pilih Pelanggan"
                    :options="
                        customers.map((c) => ({ value: c.id, label: c.name }))
                    "
                    :error="form.errors.customer_id"
                />
                <TextField
                    type="date"
                    v-model="form.due_date"
                    label="Jatuh Tempo"
                    :error="form.errors.due_date"
                />
            </div>

            <!-- Items Table -->
            <div class="space-y-4">
                <div class="flex justify-between items-end">
                    <div>
                        <h3 class="font-semibold text-gray-800">Daftar Item</h3>
                        <p class="text-sm text-gray-500">
                            Pilih produk dan tentukan jumlah serta harganya.
                        </p>
                    </div>
                    <div class="w-72">
                        <AsyncSelectField
                            :apiUrl="route('products.search')"
                            placeholder="Cari & tambah produk..."
                            @select="addProduct"
                            :apiParams="{ outlet_id: form.outlet_id }"
                        />
                    </div>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th
                                    class="px-4 py-3 font-semibold text-gray-600"
                                >
                                    Produk
                                </th>
                                <th
                                    class="px-4 py-3 font-semibold text-gray-600 w-32"
                                >
                                    Harga Satuan
                                </th>
                                <th
                                    class="px-4 py-3 font-semibold text-gray-600 w-24"
                                >
                                    Qty
                                </th>
                                <th
                                    class="px-4 py-3 font-semibold text-gray-600 w-32"
                                >
                                    Diskon (Rp)
                                </th>
                                <th
                                    class="px-4 py-3 font-semibold text-gray-600 w-32 text-right"
                                >
                                    Subtotal
                                </th>
                                <th class="px-4 py-3 w-12 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template v-if="form.items.length > 0">
                                <tr
                                    v-for="(item, index) in form.items"
                                    :key="index"
                                    class="bg-white"
                                >
                                    <td class="px-4 py-3 font-medium">
                                        {{ item.product_name }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <NumberField
                                            v-model.number="
                                                form.items[index].price
                                            "
                                            @update:modelValue="calculateTotals"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <NumberField
                                            v-model.number="
                                                form.items[index].qty
                                            "
                                            @update:modelValue="calculateTotals"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <NumberField
                                            v-model.number="
                                                form.items[index]
                                                    .discount_amount
                                            "
                                            @update:modelValue="calculateTotals"
                                        />
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-medium text-gray-700"
                                    >
                                        {{ formatCurrency(item.subtotal) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            @click="removeItem(index)"
                                            class="text-danger hover:text-red-700 bg-red-50 p-2 rounded"
                                        >
                                            <FontAwesomeIcon :icon="faTrash" />
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <template v-else>
                                <tr>
                                    <td
                                        colspan="6"
                                        class="px-4 py-8 text-center text-gray-400"
                                    >
                                        Belum ada item ditambahkan.
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Totals Summary -->
            <div class="flex justify-end pt-4">
                <div
                    class="w-full md:w-1/3 space-y-3 bg-gray-50 p-4 rounded-lg border border-gray-100"
                >
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Subtotal Item</span>
                        <span class="font-medium">{{
                            formatCurrency(form.subtotal)
                        }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Diskon Tambahan</span>
                        <div class="w-1/2">
                            <NumberField
                                v-model.number="form.discount_amount"
                                @update:modelValue="calculateTotals"
                            />
                        </div>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Pajak / PPN</span>
                        <div class="w-1/2">
                            <NumberField
                                v-model.number="form.tax_amount"
                                @update:modelValue="calculateTotals"
                            />
                        </div>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">Biaya Lainnya</span>
                        <div class="w-1/2">
                            <NumberField
                                v-model.number="form.service_charge_amount"
                                @update:modelValue="calculateTotals"
                            />
                        </div>
                    </div>
                    <hr class="border-gray-200" />
                    <div class="flex justify-between items-center text-lg">
                        <span class="font-bold text-gray-700">Total Akhir</span>
                        <span class="font-bold text-main">{{
                            formatCurrency(form.total)
                        }}</span>
                    </div>
                </div>
            </div>

            <div
                v-if="Object.keys(form.errors).length > 0"
                class="bg-red-50 text-danger p-3 rounded text-sm"
            >
                Terdapat beberapa kesalahan pengisian form. Silakan periksa
                kembali.
            </div>
        </div>
        <template #footer>
            <div class="flex justify-end gap-3">
                <Link
                    :href="route('transactions.sales.index')"
                    class="btn btn-outline-main"
                    >Batal</Link
                >
                <button
                    class="btn btn-main"
                    @click="submit"
                    :disabled="form.processing || form.items.length === 0"
                >
                    <span v-if="form.processing">Menyimpan...</span>
                    <span v-else>Simpan Faktur</span>
                </button>
            </div>
        </template>
    </MainPage>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faTrash } from '@fortawesome/free-solid-svg-icons';
import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue';
import TextField from '@/Components/Form/TextField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import AsyncSelectField from '@/Components/Form/AsyncSelectField.vue';
import { formatIDR as formatCurrency } from '@/Composable/currency-format.js';

const props = defineProps({
    customers: { type: Array, default: () => [] },
    outlets: { type: Array, default: () => [] },
});

const form = useForm({
    outlet_id: props.outlets.length > 0 ? props.outlets[0].id : '',
    customer_id: '',
    due_date: '',
    items: [],
    subtotal: 0,
    tax_amount: 0,
    discount_amount: 0,
    service_charge_amount: 0,
    total: 0,
});

const addProduct = (product) => {
    form.items.push({
        product_id: product.id,
        product_name: product.name,
        price: product.price || 0,
        qty: 1,
        discount_amount: 0,
        subtotal: product.price || 0,
    });
    calculateTotals();
};

const removeItem = (index) => {
    form.items.splice(index, 1);
    calculateTotals();
};

const calculateTotals = () => {
    let subtotal = 0;
    form.items.forEach((item) => {
        item.subtotal =
            Number(item.price || 0) * Number(item.qty || 0) -
            Number(item.discount_amount || 0);
        if (item.subtotal < 0) item.subtotal = 0;
        subtotal += item.subtotal;
    });

    form.subtotal = subtotal;

    let total =
        form.subtotal -
        Number(form.discount_amount || 0) +
        Number(form.tax_amount || 0) +
        Number(form.service_charge_amount || 0);

    form.total = total > 0 ? total : 0;
};

const submit = () => {
    form.post(route('transactions.sales.invoices.store'));
};
</script>
