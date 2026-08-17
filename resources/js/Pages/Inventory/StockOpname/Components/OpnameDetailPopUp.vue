<template>
    <div>
        <div v-if="opname" class="space-y-2">
            <div
                class="grid grid-cols-1 md:grid-cols-2 gap-2 bg-gray-50 p-4 rounded-lg"
            >
                <div>
                    <p class="mb-1">
                        <strong>Nomor Opname:</strong>
                        {{ opname.opname_number }}
                    </p>
                    <p class="mb-1">
                        <strong>Outlet:</strong> {{ opname.outlet?.name }}
                    </p>
                    <p>
                        <strong>Status:</strong>
                        <span class="badge" :class="statusColor(opname.status)">
                            {{ statusLabel(opname.status) }}
                        </span>
                    </p>
                </div>
                <div>
                    <p class="mb-1">
                        <strong>Dibuat Oleh:</strong>
                        {{ opname.creator?.name || '-' }}
                    </p>
                    <p class="mb-1">
                        <strong>Tanggal Dibuat:</strong>
                        {{ formatDateTimeID(opname.created_at) }}
                    </p>
                    <p>
                        <strong>Disetujui/Ditolak Oleh:</strong>
                        {{ opname.approver?.name || '-' }}
                    </p>
                </div>
            </div>

            <div v-if="opname.notes" class="bg-gray-50 p-4 rounded-lg">
                <p><strong>Catatan:</strong></p>
                <p class="mt-1 whitespace-pre-wrap">{{ opname.notes }}</p>
            </div>

            <div class="mt-2 border-t pt-4">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-semibold">Data Fisik Stok</h3>
                    <a
                        :href="route('inventory.opnames.export.pdf', opname.id)"
                        target="_blank"
                        class="btn btn-outline-main btn-sm"
                    >
                        <i class="fa fa-file-pdf"></i> Ekspor PDF
                    </a>
                </div>

                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <div
                        v-for="(item, index) in opname.items"
                        :key="item.id"
                        class="flex gap-2 items-center border p-2 rounded-lg bg-white"
                        :class="{
                            'bg-red-50':
                                Number(item.actual_qty) !==
                                Number(item.system_qty),
                        }"
                    >
                        <div class="w-8 text-center text-gray-500 font-bold">
                            {{ index + 1 }}
                        </div>
                        <div class="flex-1">
                            <div class="font-semibold">
                                {{ item.inventory_item?.name }}
                            </div>
                            <div class="text-sm text-gray-500">
                                SKU: {{ item.inventory_item?.sku || '-' }} |
                                Satuan:
                                {{ item.inventory_item?.uom?.name || '-' }}
                            </div>
                        </div>
                        <div class="w-32">
                            <div class="text-xs text-gray-500 text-center">
                                Stok Sistem
                            </div>
                            <div
                                class="text-center font-semibold bg-gray-100 py-1 rounded"
                            >
                                {{ formatNumberID(item.system_qty) }}
                            </div>
                        </div>
                        <div class="w-32">
                            <div class="text-xs text-gray-500 text-center">
                                Stok Fisik
                            </div>
                            <div
                                class="text-center font-semibold bg-gray-100 py-1 rounded"
                            >
                                {{ formatNumberID(item.actual_qty) }}
                            </div>
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
                    </div>
                </div>
            </div>

            <!-- Summary Footer -->
            <div
                class="mt-2a grid grid-cols-2 md:grid-cols-5 gap-2 border-t pt-2"
            >
                <div class="text-center p-3 bg-gray-50 rounded">
                    <div class="text-xs text-gray-500">Total Item</div>
                    <div class="font-bold text-lg">
                        {{ summary.totalItems }}
                    </div>
                </div>
                <div class="text-center p-3 bg-gray-50 rounded">
                    <div class="text-xs text-gray-500">Cocok</div>
                    <div class="font-bold text-lg">{{ summary.matched }}</div>
                </div>
                <div class="text-center p-3 bg-gray-50 rounded">
                    <div class="text-xs text-gray-500">Berselisih</div>
                    <div class="font-bold text-lg">{{ summary.diff }}</div>
                </div>
                <div class="text-center p-3 bg-green-50 rounded">
                    <div class="text-xs text-gray-500">Total Surplus</div>
                    <div class="font-bold text-lg text-success">
                        +{{ formatNumberID(summary.surplus) }}
                    </div>
                </div>
                <div class="text-center p-3 bg-red-50 rounded">
                    <div class="text-xs text-gray-500">Total Shortage</div>
                    <div class="font-bold text-lg text-danger">
                        -{{ formatNumberID(summary.shortage) }}
                    </div>
                </div>
            </div>
        </div>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <button
                type="button"
                class="btn btn-flat"
                :disabled="form.processing"
                @click="close"
            >
                Tutup
            </button>
            <template v-if="opname && opname.status === 'pending_approval'">
                <button
                    type="button"
                    class="btn btn-danger"
                    :disabled="form.processing"
                    @click="confirmAction('reject')"
                >
                    Tolak
                </button>
                <button
                    type="button"
                    class="btn btn-main"
                    :disabled="form.processing"
                    @click="confirmAction('approve')"
                >
                    Setujui & Sesuaikan Stok
                </button>
            </template>
        </Teleport>
    </div>
    <!-- Confirmation Modal for Approve / Reject -->
    <Modal
        :class="{ show: showConfirm, hide: !showConfirm }"
        :title="confirmTitle"
        @close="showConfirm = false"
    >
        <p class="text-gray-600 mb-4">{{ confirmMessage }}</p>

        <div v-if="actionType === 'reject'">
            <TextareaField
                id="reject_notes"
                v-model="form.notes"
                label="Alasan Penolakan (Wajib)"
                :class="{ 'is-invalid': form.errors.notes }"
                :error="form.errors.notes"
            />
        </div>

        <template #footer>
            <button class="btn btn-flat" @click="showConfirm = false">
                Batal
            </button>
            <button
                class="btn"
                :class="actionType === 'reject' ? 'btn-danger' : 'btn-main'"
                @click="executeAction"
            >
                Konfirmasi
            </button>
        </template>
    </Modal>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Notifications/Modal.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';
import { formatDateTimeID } from '@/Composable/date';
import { formatNumberID } from '@/Composable/useNumberFormat';
import { useModalStore } from '@/store/notification';

const modal = useModalStore();

const props = defineProps({
    opname: Object,
});

const emit = defineEmits(['close']);
const isMounted = ref(false);

onMounted(() => {
    isMounted.value = true;
});

const form = useForm({
    notes: '',
    items: [], // we will inject this just to satisfy UpdateStockOpnameRequest validation for approve method
});

const showConfirm = ref(false);
const confirmTitle = ref('');
const confirmMessage = ref('');
const actionType = ref('');

const summary = computed(() => {
    let totalItems = 0;
    let matched = 0;
    let diffCount = 0;
    let surplus = 0;
    let shortage = 0;

    if (props.opname && props.opname.items) {
        totalItems = props.opname.items.length;
        props.opname.items.forEach((item) => {
            const diff = Number(item.difference_qty);
            if (diff === 0) matched++;
            else diffCount++;

            if (diff > 0) surplus += diff;
            if (diff < 0) shortage += Math.abs(diff);
        });
    }

    return { totalItems, matched, diff: diffCount, surplus, shortage };
});

const statusLabel = (status) => {
    const labels = {
        in_progress: 'Sedang Berjalan',
        pending_approval: 'Menunggu Persetujuan',
        approved: 'Disetujui',
        rejected: 'Ditolak',
    };
    return labels[status] || status;
};

const statusColor = (status) => {
    const colors = {
        in_progress: 'badge-warning',
        pending_approval: 'badge-info',
        approved: 'badge-success',
        rejected: 'badge-danger',
    };
    return colors[status] || 'badge-gray';
};

const differenceColor = (actual, system) => {
    const diff = Number(actual || 0) - Number(system || 0);
    if (diff > 0) return 'text-success';
    if (diff < 0) return 'text-danger';
    return 'text-gray-400';
};

const formatDifference = (actual, system) => {
    const diff = Number(actual || 0) - Number(system || 0);
    const formatted = formatNumberID(Math.abs(diff));
    if (diff > 0) return '+' + formatted;
    if (diff < 0) return '-' + formatted;
    return '0';
};

const close = () => {
    form.clearErrors();
    emit('close');
};

const confirmAction = (type) => {
    actionType.value = type;
    form.clearErrors();

    if (type === 'approve') {
        modal.open({
            title: 'Setujui & Sesuaikan Stok',
            message:
                'Stok sistem akan langsung disesuaikan dengan hasil penghitungan fisik ini. Apakah Anda yakin?',
            confirmText: 'Setujui & Sesuaikan Stok',
            confirmButtonClass: 'btn btn-main',
            onConfirm: () => {
                form.items = props.opname.items.map((i) => ({
                    inventory_item_id: i.inventory_item_id,
                    system_qty: i.system_qty,
                    actual_qty: i.actual_qty,
                }));

                executeAction();
            },
        });
    } else {
        modal.open({
            title: 'Tolak Opname',
            message:
                'Opname akan ditolak dan stok tidak akan disesuaikan. Silakan beri alasan.',
            confirmText: 'Tolak Opname',
            confirmButtonClass: 'btn btn-danger',
            onConfirm: () => {
                form.notes = '';
                executeAction();
            },
        });
    }
    showConfirm.value = true;
};

const executeAction = () => {
    const options = {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            showConfirm.value = false;
            close();
        },
    };

    if (actionType.value === 'approve') {
        form.post(route('inventory.opnames.approve', props.opname.id), options);
    } else {
        form.post(route('inventory.opnames.reject', props.opname.id), options);
    }
};
</script>
