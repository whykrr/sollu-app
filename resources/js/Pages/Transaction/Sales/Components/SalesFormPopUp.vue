<template>
    <div class="space-y-4 pb-24">
        <!-- Stock Validation Error Alert -->

        <!-- Document Information -->
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-slate-700 uppercase">
                Informasi Dokumen
            </h3>
            <div class="grid grid-cols-1 gap-2">
                <AsyncOutletDropdown
                    v-model="form.outlet_id"
                    label="Outlet"
                    placeholder="Pilih Outlet"
                    :error="form.errors.outlet_id"
                    required
                />

                <div>
                    <label class="block text-sm font-medium mb-1"
                        >Pelanggan</label
                    >
                    <div
                        v-if="selectedCustomer"
                        class="flex items-center justify-between p-1 bg-slate-50 border border-slate-200 rounded-xl"
                    >
                        <div class="flex items-center">
                            <div
                                class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-bold text-xs"
                            >
                                <FontAwesomeIcon :icon="faUser" />
                            </div>
                            <div>
                                <div
                                    class="font-semibold text-sm text-slate-800"
                                >
                                    {{ selectedCustomer.name }}
                                </div>
                                <div
                                    v-if="selectedCustomer.phone"
                                    class="text-xs text-slate-500"
                                >
                                    {{ selectedCustomer.phone }}
                                </div>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="clearCustomer"
                            class="btn btn-ghost btn-xs text-slate-400 hover:text-rose-600"
                            title="Ganti Pelanggan"
                        >
                            <FontAwesomeIcon :icon="faTimes" />
                        </button>
                    </div>
                    <AsyncSelectField
                        v-else
                        id="customer_id"
                        v-model="form.customer_id"
                        placeholder="Cari Pelanggan (Ketik nama / no HP)..."
                        :api-url="route('api.internal.customers.search')"
                        :error="form.errors.customer_id"
                        @select="onCustomerSelected"
                    />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <DropdownField
                    v-model="form.channel"
                    label="Channel Penjualan"
                    :options="channelOptions"
                    :error="form.errors.channel"
                    required
                />
                <TextField
                    type="date"
                    v-model="form.transaction_date"
                    label="Tanggal Transaksi"
                    :error="form.errors.transaction_date"
                    required
                />
            </div>

            <div class="grid grid-cols-2 gap-2">
                <DropdownField
                    v-model="form.payment_term"
                    label="Metode Pembayaran"
                    :options="[
                        { value: 'cash', label: 'Tunai' },
                        { value: 'credit', label: 'Kredit / Termin' },
                    ]"
                    :error="form.errors.payment_term"
                    required
                />

                <TextField
                    v-if="form.payment_term === 'credit'"
                    type="date"
                    v-model="form.due_date"
                    label="Tanggal Jatuh Tempo"
                    :error="form.errors.due_date"
                    :disabled="!can('transaction.edit_due_date')"
                    required
                />
            </div>
        </div>

        <hr class="border-slate-200" />

        <!-- Item List -->
        <div class="space-y-4">
            <div class="flex justify-between items-center mb-2">
                <h3 class="text-sm font-semibold text-slate-700 uppercase">
                    Daftar Item
                </h3>
                <div class="w-72">
                    <AsyncSelectField
                        id="search_product"
                        label="Cari Produk (Min. 3 huruf)"
                        placeholder="Cari nama, kode produk..."
                        class="sm"
                        :api-url="
                            route('api.internal.products.search-by-inventory')
                        "
                        :api-params="{ outlet_id: form.outlet_id }"
                        :min-chars="3"
                        :disabled="!form.outlet_id"
                        @select="selectProduct"
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
                                        Kode: {{ item.code || '-' }}
                                        <span
                                            v-if="item.track_inventory"
                                            class="ml-2"
                                        >
                                            Stok:
                                            <span
                                                :class="
                                                    item.current_stock > 0
                                                        ? 'text-gray-700 font-medium'
                                                        : 'text-rose-600 font-semibold'
                                                "
                                                >{{ item.current_stock }}</span
                                            >
                                        </span>
                                        <span
                                            v-else
                                            class="ml-2 text-slate-400"
                                        >
                                            (Tidak di-track)
                                        </span>
                                    </div>
                                </div>
                                <div
                                    class="text-right text-xs font-bold text-slate-800"
                                >
                                    {{ formatCurrency(item.price || 0) }}
                                </div>
                            </div>
                        </template>
                    </AsyncSelectField>
                </div>
            </div>

            <div v-if="!form.outlet_id" class="text-xs text-rose-500 py-1">
                Pilih outlet terlebih dahulu.
            </div>

            <div
                v-if="form.items.length === 0"
                class="text-center py-6 text-gray-500 border rounded-lg bg-gray-50/50"
            >
                Belum ada item ditambahkan.
            </div>

            <div v-else class="space-y-2">
                <div
                    v-for="(item, index) in form.items"
                    :key="index"
                    class="p-4 bg-slate-50 border border-slate-200 rounded-lg space-y-4 relative"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-medium text-sm text-slate-800">
                                {{ item.product_name }}
                            </div>
                            <div
                                v-if="item.promo_name"
                                class="text-xs text-emerald-600 font-medium mt-0.5"
                            >
                                Promo: {{ item.promo_name }}
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="removeItem(index)"
                            class="btn btn-flat text-danger"
                        >
                            <FontAwesomeIcon :icon="faTrash" />
                        </button>
                    </div>

                    <div class="grid grid-cols-4 gap-4">
                        <NumberField
                            v-model="item.price"
                            label="Harga"
                            prefix="Rp"
                            @update:modelValue="calculateTotals"
                        />
                        <NumberField
                            v-model="item.qty"
                            label="Kuantitas"
                            @update:modelValue="calculateTotals"
                        />
                        <NumberField
                            v-model="item.discount_amount"
                            label="Diskon (Rp)"
                            prefix="Rp"
                            :disabled="!can('transaction.discount_manual')"
                            @update:modelValue="calculateTotals"
                        />
                        <NumberField
                            v-model="item.subtotal"
                            label="Subtotal"
                            prefix="Rp"
                            disabled
                        />
                    </div>
                </div>
            </div>
            <div
                v-if="form.errors.items"
                class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium rounded-lg flex items-center gap-2"
            >
                <span>⚠️ {{ form.errors.items }}</span>
            </div>
        </div>

        <hr class="border-slate-200" />

        <!-- Promo & Discount -->
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-slate-700 uppercase">
                Promo & Diskon Tambahan
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <AsyncSelectField
                        id="promo_search"
                        label="Cari Promo"
                        placeholder="Pilih promo aktif (Opsional)..."
                        :api-url="route('api.internal.promos.search')"
                        :api-params="{ outlet_id: form.outlet_id }"
                        :min-chars="0"
                        :disabled="!form.outlet_id"
                        @select="onPromoSelected"
                    >
                        <template #option="{ item }">
                            <div
                                class="flex justify-between items-center w-full"
                            >
                                <div>
                                    <div
                                        class="font-semibold text-sm text-slate-800"
                                    >
                                        {{ item.name }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{
                                            item.target_type === 'bill'
                                                ? 'Per Total Bill'
                                                : 'Per Produk'
                                        }}
                                        -
                                        {{
                                            item.promo_type === 'percentage'
                                                ? item.discount_value + '%'
                                                : formatCurrency(
                                                      item.discount_value,
                                                  )
                                        }}
                                    </div>
                                </div>
                            </div>
                        </template>
                    </AsyncSelectField>
                    <div
                        v-if="selectedPromo"
                        class="flex items-center justify-between text-xs text-emerald-700 bg-emerald-50 p-2 rounded-lg border border-emerald-200 mt-1"
                    >
                        <span
                            >Promo aktif:
                            <strong>{{ selectedPromo.name }}</strong></span
                        >
                        <button
                            type="button"
                            @click="clearPromo"
                            class="text-rose-600 font-semibold hover:underline"
                        >
                            Batalkan Promo
                        </button>
                    </div>
                </div>
                <div>
                    <NumberField
                        v-model="form.manual_discount_amount"
                        label="Diskon Manual Dokumen (Rp)"
                        prefix="Rp"
                        :disabled="!can('transaction.discount_manual')"
                        @update:modelValue="calculateTotals"
                    />
                </div>
            </div>
        </div>

        <hr class="border-slate-200" />

        <!-- Order Summary -->
        <div class="space-y-2 text-sm bg-slate-50 p-4 rounded-lg">
            <div class="flex justify-between">
                <span class="text-slate-500">Subtotal</span>
                <span class="font-medium">{{
                    formatCurrency(form.subtotal)
                }}</span>
            </div>
            <div class="flex justify-between" v-if="form.discount_amount > 0">
                <span class="text-slate-500">Diskon</span>
                <span class="font-medium text-danger"
                    >-{{ formatCurrency(form.discount_amount) }}</span
                >
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Biaya Pengiriman</span>
                <div class="w-32">
                    <NumberField
                        v-model="form.shipping_fee"
                        @update:modelValue="calculateTotals"
                        class="!py-1"
                    />
                </div>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Pajak</span>
                <div class="w-32">
                    <NumberField
                        v-model="form.tax_amount"
                        @update:modelValue="calculateTotals"
                        class="!py-1"
                    />
                </div>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Service Charge</span>
                <div class="w-32">
                    <NumberField
                        v-model="form.service_charge_amount"
                        @update:modelValue="calculateTotals"
                        class="!py-1"
                    />
                </div>
            </div>
            <div
                class="flex justify-between border-t border-slate-200 pt-2 mt-2"
            >
                <span class="font-bold text-lg">Total</span>
                <span class="font-bold text-lg text-primary">{{
                    formatCurrency(form.total)
                }}</span>
            </div>
        </div>

        <hr class="border-slate-200" />

        <!-- Additional Information -->
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-slate-700 uppercase">
                Informasi Tambahan
            </h3>
            <TextareaField
                v-model="form.notes"
                label="Catatan"
                placeholder="Catatan internal..."
                :error="form.errors.notes"
            />
            <TextareaField
                v-model="form.terms_and_conditions"
                label="Syarat & Ketentuan"
                placeholder="Syarat dan ketentuan invoice..."
                :error="form.errors.terms_and_conditions"
            />
        </div>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex items-center justify-between w-full">
                <button
                    type="button"
                    class="btn btn-flat"
                    @click="popUpStore.close()"
                >
                    Batal
                </button>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="btn btn-outline"
                        @click="submit('draft')"
                        :disabled="form.processing"
                        v-if="can('transaction.create')"
                    >
                        Simpan Draf
                    </button>
                    <button
                        type="button"
                        class="btn btn-main"
                        @click="submit('issue')"
                        :disabled="form.processing"
                        v-if="can('transaction.issue_invoice')"
                    >
                        Terbitkan Invoice
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePopUpStore } from '@/store/popup';
import { useModalStore } from '@/store/notification.js';
import axios from 'axios';
import { debounce } from 'lodash';
import { useAuth } from '@/Composable/useAuth';
import { formatIDR as formatCurrency } from '@/Composable/currency-format';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faTrash,
    faPlus,
    faTimes,
    faUser,
} from '@fortawesome/free-solid-svg-icons';

import TextField from '@/Components/Form/TextField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';
import AsyncSelectField from '@/Components/Form/AsyncSelectField.vue';
import AsyncOutletDropdown from '@/Components/Form/AsyncOutletDropdown.vue';

const { can } = useAuth();
const popUpStore = usePopUpStore();
const modalStore = useModalStore();
const isMounted = ref(false);
const selectedPromo = ref(null);

const props = defineProps({
    transaction: {
        type: Object,
        default: null,
    },
});

const selectedCustomer = ref(null);

const onCustomerSelected = (customer) => {
    selectedCustomer.value = customer;
    form.customer_id = customer?.value || customer?.id || '';
};

const clearCustomer = () => {
    selectedCustomer.value = null;
    form.customer_id = '';
};

const channelOptions = [
    { value: 'direct', label: 'Direct / B2B' },
    { value: 'e_commerce', label: 'E-Commerce' },
    { value: 'wholesale', label: 'Wholesale' },
    { value: 'custom', label: 'Custom' },
];

const form = useForm({
    outlet_id: '',
    customer_id: '',
    channel: 'direct',
    transaction_date: new Date().toISOString().split('T')[0],
    payment_term: 'cash',
    due_date: '',
    items: [],
    manual_discount_amount: 0,
    promo_id: '',
    promo_discount_amount: 0,
    shipping_fee: 0,
    tax_amount: 0,
    service_charge_amount: 0,
    notes: '',
    terms_and_conditions: '',
    action: 'draft', // draft or issue
    subtotal: 0,
    discount_amount: 0,
    total: 0,
});

watch(
    () => form.outlet_id,
    (val) => {
        if (val && !props.transaction) {
            form.items = [];
            clearPromo();
        }
    },
);

const removeItem = (index) => {
    form.items.splice(index, 1);
    calculateTotals();
};

const onPromoSelected = (promo) => {
    selectedPromo.value = promo;
    form.promo_id = promo?.id || '';
    calculateTotals();
};

const clearPromo = () => {
    selectedPromo.value = null;
    form.promo_id = '';
    form.promo_discount_amount = 0;
    calculateTotals();
};

const calculateTotals = () => {
    let subtotal = 0;

    form.items.forEach((item) => {
        if (item.promo_name && item.max_promo_discount !== undefined) {
            const maxAllowed = Number(item.max_promo_discount) * Number(item.qty);
            if (Number(item.discount_amount) > maxAllowed) {
                item.discount_amount = maxAllowed;
            }
        }
        
        item.subtotal =
            Number(item.qty) * Number(item.price) -
            Number(item.discount_amount || 0);
        if (item.subtotal < 0) item.subtotal = 0;
        subtotal += item.subtotal;
    });

    form.subtotal = subtotal;

    // Document / Bill level promo calculation
    let promoDiscount = 0;
    if (selectedPromo.value) {
        const promo = selectedPromo.value;
        if (promo.target_type === 'bill') {
            if (promo.promo_type === 'percentage') {
                promoDiscount = (subtotal * Number(promo.discount_value)) / 100;
                if (
                    promo.max_discount &&
                    promoDiscount > Number(promo.max_discount)
                ) {
                    promoDiscount = Number(promo.max_discount);
                }
            } else if (promo.promo_type === 'fixed') {
                promoDiscount = Math.min(
                    Number(promo.discount_value),
                    subtotal,
                );
            }
        }
    }
    form.promo_discount_amount = promoDiscount;

    // Total discount combines manual discount and document promo discount
    form.discount_amount =
        Number(form.manual_discount_amount || 0) +
        Number(form.promo_discount_amount || 0);

    let total =
        subtotal -
        form.discount_amount +
        Number(form.shipping_fee || 0) +
        Number(form.tax_amount || 0) +
        Number(form.service_charge_amount || 0);

    form.total = total > 0 ? total : 0;
};

const selectProduct = (product) => {
    if (!product) return;

    const targetProductId = product.product_id || product.id;

    // 1. Stock check if track_inventory is enabled
    if (product.track_inventory) {
        const existingInCart = form.items.find(
            (i) => i.product_id === targetProductId,
        );
        const currentQtyInCart = existingInCart
            ? Number(existingInCart.qty)
            : 0;
        const availableStock =
            Number(product.current_stock ?? 0) - currentQtyInCart;

        if (availableStock <= 0) {
            modalStore.alert({
                type: 'warning',
                title: 'Stok Kosong / Tidak Mencukupi',
                message: `Stok untuk produk "${product.name}" tidak tersedia atau sudah habis (${Number(product.current_stock ?? 0)} tersisa). Produk dengan stok kosong tidak dapat dijual.`,
                confirmText: 'Mengerti',
            });
            return;
        }
    }

    // 2. Proceed to promo check if stock is available or not tracked
    checkAndApplyProductPromo(product);
};

const checkAndApplyProductPromo = (product) => {
    if (product.active_promos && product.active_promos.length > 0) {
        const promo = product.active_promos[0];
        modalStore.open({
            type: 'info',
            title: 'Promo Produk Tersedia!',
            message: `Produk "${product.name}" memiliki promo "${promo.name}". Apakah Anda ingin menerapkan promo ini?`,
            confirmButtonText: 'Ya, Terapkan Promo',
            cancelButtonText: 'Abaikan',
            onConfirm: () => {
                addProductToItems(product, promo);
            },
            onCancel: () => {
                addProductToItems(product, null);
            },
        });
    } else {
        addProductToItems(product, null);
    }
};

const addProductToItems = (product, promo = null) => {
    const targetProductId = product.inventory_item_id || product.id;
    const existingIndex = form.items.findIndex(
        (i) => i.inventory_item_id === targetProductId,
    );

    let promoDiscount = 0;
    let promoName = null;

    if (promo) {
        promoName = promo.name;
        if (promo.promo_type === 'percentage') {
            promoDiscount =
                (Number(product.price) * Number(promo.discount_value)) / 100;
            if (
                promo.max_discount &&
                promoDiscount > Number(promo.max_discount)
            ) {
                promoDiscount = Number(promo.max_discount);
            }
        } else if (promo.promo_type === 'fixed') {
            promoDiscount = Math.min(
                Number(promo.discount_value),
                Number(product.price),
            );
        }
    }

    if (existingIndex !== -1) {
        const item = form.items[existingIndex];
        item.qty = Number(item.qty) + 1;
        if (promo) {
            item.promo_name = promoName;
            item.max_promo_discount = promoDiscount;
            item.discount_amount = promoDiscount * item.qty;
        }
    } else {
        form.items.unshift({
            product_id: targetProductId,
            inventory_item_id: product.inventory_item_id || product.id,
            variant_group_option_id: product.variant_group_option_id || null,
            product_name: product.name,
            qty: 1,
            price: Number(product.price) || 0,
            discount_amount: promoDiscount,
            max_promo_discount: promoDiscount,
            promo_name: promoName,
            subtotal: (Number(product.price) || 0) - promoDiscount,
        });
    }

    calculateTotals();
};

watch(
    () => props.transaction,
    (data) => {
        if (data) {
            form.outlet_id = data.outlet_id || '';
            form.customer_id = data.customer_id || '';
            form.channel = data.channel || 'direct';
            form.transaction_date =
                data.transaction_date || new Date().toISOString().split('T')[0];
            form.due_date = data.due_date || '';
            form.payment_term = data.payment_term || 'tunai';
            form.manual_discount_amount = Number(data.discount_amount) || 0;
            form.shipping_fee = Number(data.shipping_fee) || 0;
            form.tax_amount = Number(data.tax_amount) || 0;
            form.service_charge_amount =
                Number(data.service_charge_amount) || 0;
            form.notes = data.notes || '';
            form.terms_and_conditions = data.terms_and_conditions || '';

            if (data.customer) {
                selectedCustomer.value = data.customer;
            }

            if (data.items && data.items.length > 0) {
                form.items = data.items.map((i) => ({
                    id: i.id,
                    product_id: i.product_id,
                    product_name: i.product_name,
                    qty: Number(i.qty),
                    price: Number(i.price),
                    discount_amount: Number(i.discount_amount),
                    subtotal: Number(i.subtotal),
                }));
            }

            calculateTotals();
        }
    },
    { immediate: true },
);

const submit = (actionType) => {
    form.action = actionType;
    form.post(route('transactions.sales.store'), {
        preserveScroll: true,
        onSuccess: () => {
            popUpStore.close();
        },
    });
};

onMounted(() => {
    isMounted.value = true;
});
</script>
