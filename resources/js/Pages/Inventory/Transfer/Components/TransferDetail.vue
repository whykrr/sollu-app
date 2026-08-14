<template>
    <div>
        <div v-if="loading" class="p-6 text-center text-gray-500">
            Memuat data...
        </div>
        <div v-else-if="transferData" class="space-y-2">
            <!-- Header Info -->
            <div
                class="grid grid-cols-1 md:grid-cols-2 gap-2 bg-gray-50 p-4 rounded-lg"
            >
                <div>
                    <p class="text-sm text-gray-500">No. Transfer</p>
                    <p class="font-semibold">
                        {{ transferData.transfer_number }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <span
                        class="badge"
                        :class="statusColor(transferData.status)"
                    >
                        {{ statusLabel(transferData.status) }}
                    </span>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Dari Outlet</p>
                    <p class="font-semibold">
                        {{
                            transferData.from_outlet?.name ||
                            transferData.fromOutlet?.name ||
                            '-'
                        }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Ke Outlet</p>
                    <p class="font-semibold">
                        {{
                            transferData.to_outlet?.name ||
                            transferData.toOutlet?.name ||
                            '-'
                        }}
                    </p>
                </div>
                <div v-if="transferData.notes" class="col-span-full">
                    <p class="text-sm text-gray-500">Catatan</p>
                    <p>{{ transferData.notes }}</p>
                </div>
            </div>

            <!-- Audit Trail -->
            <div
                class="grid grid-cols-1 md:grid-cols-3 gap-2 text-sm text-gray-600"
            >
                <div>
                    <p><strong>Pemohon:</strong></p>
                    <p>
                        {{ transferData.requester?.name || '-' }} ({{
                            formatDate(transferData.created_at)
                        }})
                    </p>
                </div>
                <div>
                    <p><strong>Penyetuju:</strong></p>
                    <p>{{ transferData.approver?.name || '-' }}</p>
                </div>
                <div>
                    <p><strong>Penerima:</strong></p>
                    <p>{{ transferData.receiver?.name || '-' }}</p>
                </div>
            </div>

            <!-- Items Table -->
            <div>
                <h3 class="text-lg font-semibold mb-3">Item yang Ditransfer</h3>
                <div class="overflow-x-auto border rounded-lg">
                    <table class="table w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <th>Nama Item</th>
                                <th>Satuan</th>
                                <th class="text-right">Qty Dikirim</th>
                                <th
                                    v-if="
                                        ['completed', 'rejected'].includes(
                                            transferData.status,
                                        )
                                    "
                                    class="text-right"
                                >
                                    Qty Diterima
                                </th>
                                <th
                                    v-if="
                                        ['completed', 'rejected'].includes(
                                            transferData.status,
                                        )
                                    "
                                    class="text-right"
                                >
                                    Selisih
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in transferData.items"
                                :key="item.id"
                            >
                                <td>
                                    {{
                                        item.inventory_item?.name ||
                                        item.inventoryItem?.name ||
                                        '-'
                                    }}
                                </td>
                                <td>
                                    {{
                                        item.inventory_item?.uom?.name ||
                                        item.inventoryItem?.uom?.name ||
                                        '-'
                                    }}
                                </td>
                                <td class="text-right">
                                    {{ item.qty_formatted }}
                                </td>
                                <td
                                    v-if="
                                        ['completed', 'rejected'].includes(
                                            transferData.status,
                                        )
                                    "
                                    class="text-right"
                                >
                                    {{ item.qty_received_formatted }}
                                </td>
                                <td
                                    v-if="
                                        ['completed', 'rejected'].includes(
                                            transferData.status,
                                        )
                                    "
                                    class="text-right"
                                >
                                    <span
                                        :class="{
                                            'text-danger font-semibold':
                                                item.qty > item.qty_received,
                                        }"
                                    >
                                        {{
                                            formatNumber(
                                                item.qty - item.qty_received,
                                            )
                                        }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td
                                    colspan="2"
                                    class="font-semibold text-right"
                                >
                                    Total Item:
                                </td>
                                <td class="font-semibold text-right">
                                    {{ transferData.items?.length || 0 }}
                                </td>
                                <td
                                    v-if="
                                        ['completed', 'rejected'].includes(
                                            transferData.status,
                                        )
                                    "
                                    colspan="2"
                                ></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Reject Form -->
            <div
                v-if="showRejectForm"
                class="bg-red-50 p-4 rounded-lg border border-red-200"
            >
                <h4 class="text-red-700 font-semibold mb-2">Tolak Transfer</h4>
                <TextareaField
                    id="reject_notes"
                    v-model="rejectForm.notes"
                    label="Alasan Penolakan"
                    required
                />
                <div class="mt-2 flex justify-end gap-2">
                    <button
                        type="button"
                        class="btn btn-flat btn-sm"
                        @click="showRejectForm = false"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="btn btn-danger btn-sm"
                        :disabled="rejectForm.processing || !rejectForm.notes"
                        @click="submitReject"
                    >
                        Konfirmasi Tolak
                    </button>
                </div>
            </div>
        </div>

        <Teleport v-if="isMounted && transferData" to="#popUpFooter">
            <div class="flex justify-between w-full">
                <div class="flex gap-2">
                    <button type="button" class="btn btn-flat" @click="close">
                        Tutup
                    </button>
                    <a
                        :href="
                            route('inventory.transfers.export.pdf', transferId)
                        "
                        target="_blank"
                        class="btn btn-flat text-danger"
                    >
                        <FontAwesomeIcon :icon="faFilePdf" /> Cetak PDF
                    </a>
                </div>

                <div class="flex gap-2">
                    <template v-if="transferData.status === 'pending'">
                        <button
                            v-if="canApprove && !showRejectForm"
                            type="button"
                            class="btn btn-danger"
                            @click="showRejectForm = true"
                        >
                            Tolak
                        </button>
                        <button
                            v-if="canApprove"
                            type="button"
                            class="btn btn-main"
                            :disabled="actionForm.processing"
                            @click="submitApprove"
                        >
                            Setujui
                        </button>
                    </template>

                    <template v-else-if="transferData.status === 'approved'">
                        <button
                            v-if="canShip"
                            type="button"
                            class="btn btn-info"
                            :disabled="actionForm.processing"
                            @click="submitShip"
                        >
                            Kirim Barang
                        </button>
                    </template>

                    <template v-else-if="transferData.status === 'in_transit'">
                        <button
                            v-if="canReceive"
                            type="button"
                            class="btn btn-main"
                            @click="emit('openReceive', transferData)"
                        >
                            Terima Barang
                        </button>
                    </template>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faFilePdf } from '@fortawesome/free-solid-svg-icons';
import { usePopUpStore } from '@/store/popup';
import TextareaField from '@/Components/Form/TextareaField.vue';
import { useAuth } from '@/Composable/useAuth';

const props = defineProps({
    transferId: String,
});

const emit = defineEmits(['openReceive', 'refresh']);
const popUpStore = usePopUpStore();
const isMounted = ref(false);
const { user, can, canAny } = useAuth();

const canApprove = computed(() => {
    const hasApprovePerm = canAny(['inventory.transfer.approve', 'business.*']);
    const isSelf = transferData.value?.requester?.id === user.value?.id;

    if (can('business.*')) return true;
    return hasApprovePerm && !isSelf;
});
const canShip = computed(() =>
    canAny(['inventory.transfer.ship', 'business.*']),
);
const canReceive = computed(() =>
    canAny(['inventory.transfer.receive', 'business.*']),
);

const loading = ref(false);
const transferData = ref(null);
const showRejectForm = ref(false);

const actionForm = useForm({});
const rejectForm = useForm({ notes: '' });

const fetchDetail = async () => {
    if (!props.transferId) return;
    loading.value = true;
    try {
        const response = await axios.get(
            route('inventory.transfers.show', props.transferId),
        );
        transferData.value = response.data.data;
    } catch (error) {
        console.error('Gagal mengambil detail transfer', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    isMounted.value = true;
    showRejectForm.value = false;
    rejectForm.reset();
    fetchDetail();
});

const close = () => {
    popUpStore.close();
};

const submitApprove = () => {
    actionForm.post(route('inventory.transfers.approve', props.transferId), {
        preserveScroll: true,
        onSuccess: () => {
            fetchDetail();
            emit('refresh');
        },
    });
};

const submitShip = () => {
    actionForm.post(route('inventory.transfers.ship', props.transferId), {
        preserveScroll: true,
        onSuccess: () => {
            fetchDetail();
            emit('refresh');
        },
    });
};

const submitReject = () => {
    rejectForm.post(route('inventory.transfers.reject', props.transferId), {
        preserveScroll: true,
        onSuccess: () => {
            showRejectForm.value = false;
            fetchDetail();
            emit('refresh');
        },
    });
};

const statusLabel = (status) => {
    const labels = {
        pending: 'Menunggu',
        approved: 'Disetujui',
        in_transit: 'Dalam Perjalanan',
        completed: 'Selesai',
        rejected: 'Ditolak',
    };
    return labels[status] || status;
};

const statusColor = (status) => {
    const colors = {
        pending: 'badge-warning',
        approved: 'badge-info',
        in_transit: 'badge-purple',
        completed: 'badge-success',
        rejected: 'badge-danger',
    };
    return colors[status] || 'badge-gray';
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatNumber = (num) => {
    return Number(num || 0).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
};
</script>
