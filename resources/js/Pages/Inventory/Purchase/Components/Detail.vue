<template>
    <PopUpPage
        :class="{ show: show }"
        title="Detail Purchase Order"
        :sub-title="show && purchase ? '#' + purchase.po_number : ''"
        size="lg"
        @close="close"
    >
        <div v-if="purchase" class="space-y-4">
            <!-- Header Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-gray-500 text-sm mb-1">Status PO</h3>
                    <div class="font-bold">
                        <span
                            class="badge"
                            :class="statusColor(purchase.status)"
                        >
                            {{ statusLabel(purchase.status) }}
                        </span>
                    </div>
                    <div class="mt-3">
                        <h3 class="text-gray-500 text-sm mb-1">
                            Tanggal Dibuat
                        </h3>
                        <p class="font-semibold">
                            {{ formatDateTimeID(purchase.created_at) }}
                        </p>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="mb-3">
                        <h3 class="text-gray-500 text-sm mb-1">
                            Kepada (Supplier)
                        </h3>
                        <p class="font-semibold">
                            {{ purchase.supplier?.name || '-' }}
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ purchase.supplier?.address || '' }}
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ purchase.supplier?.phone || '' }}
                        </p>
                    </div>
                    <div>
                        <h3 class="text-gray-500 text-sm mb-1">
                            Dikirim ke (Outlet)
                        </h3>
                        <p class="font-semibold">
                            {{ purchase.outlet?.name || '-' }}
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ purchase.outlet?.address || '' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Items Section -->
            <div>
                <h3 class="text-lg font-semibold mb-3 border-b pb-2">
                    Daftar Barang
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-3 border-b font-semibold">
                                    Nama Barang
                                </th>
                                <th
                                    class="p-3 border-b font-semibold text-center"
                                >
                                    Jml Pesan
                                </th>
                                <th
                                    v-if="purchase.status === 'received'"
                                    class="p-3 border-b font-semibold text-center"
                                >
                                    Jml Terima
                                </th>
                                <th
                                    class="p-3 border-b font-semibold text-right"
                                >
                                    Harga Satuan
                                </th>
                                <th
                                    class="p-3 border-b font-semibold text-right"
                                >
                                    Subtotal
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in purchase.items"
                                :key="item.id"
                                class="border-b hover:bg-gray-50"
                            >
                                <td class="p-3">
                                    <div class="font-medium">
                                        {{ item.inventoryItem?.name || '-' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Satuan: {{ item.uom?.name || '-' }}
                                    </div>
                                </td>
                                <td class="p-3 text-center">
                                    {{ formatQuantity(item.qty_ordered) }}
                                </td>
                                <td
                                    v-if="purchase.status === 'received'"
                                    class="p-3 text-center font-medium text-green-600"
                                >
                                    {{ formatQuantity(item.qty_received) }}
                                    <div
                                        v-if="
                                            item.conversion_factor &&
                                            item.conversion_factor != 1
                                        "
                                        class="text-xs text-gray-500 mt-1"
                                    >
                                        (Masuk Stok:
                                        {{
                                            formatQuantity(
                                                item.qty_received *
                                                    item.conversion_factor,
                                            )
                                        }}
                                        {{ item.inventoryItem?.uom?.name }})
                                    </div>
                                </td>
                                <td class="p-3 text-right">
                                    {{ formatCurrency(item.purchase_price) }}
                                </td>
                                <td class="p-3 text-right font-medium">
                                    {{ formatCurrency(item.subtotal) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 font-bold">
                                <td
                                    :colspan="
                                        purchase.status === 'received' ? 4 : 3
                                    "
                                    class="p-3 text-right"
                                >
                                    TOTAL KESELURUHAN
                                </td>
                                <td class="p-3 text-right text-lg text-primary">
                                    {{ formatCurrency(purchase.total_amount) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div
                v-if="purchase.notes"
                class="bg-yellow-50 p-4 rounded-lg border border-yellow-200"
            >
                <h3 class="text-sm font-semibold text-yellow-800 mb-1">
                    Catatan:
                </h3>
                <p class="text-sm text-yellow-700 whitespace-pre-wrap">
                    {{ purchase.notes }}
                </p>
            </div>
        </div>

        <template #footer>
            <button type="button" class="btn btn-flat" @click="close">
                Tutup
            </button>
            <a
                v-if="purchase"
                :href="route('inventory.purchases.pdf', purchase.id)"
                target="_blank"
                class="btn btn-outline-main"
            >
                Download PDF
            </a>
        </template>
    </PopUpPage>
</template>

<script setup>
import { formatDateTimeID } from '@/Composable/date';
import PopUpPage from '@/Components/UI/PopUpPage.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    purchase: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
    }).format(value);
};

const formatQuantity = (value) => {
    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(
        Number(value || 0),
    );
};

const statusLabel = (status) => {
    const labels = {
        draft: 'Draft',
        ordered: 'Ordered',
        received: 'Received',
        cancelled: 'Dibatalkan',
    };
    return labels[status] || status;
};

const statusColor = (status) => {
    const colors = {
        draft: 'badge-gray',
        ordered: 'badge-info',
        received: 'badge-success',
        cancelled: 'badge-danger',
    };
    return colors[status] || 'badge-gray';
};
</script>
