<template>
    <PopUpPage
        :class="{ show: show }"
        title="Detail Penyesuaian Stok"
        size="lg"
        @close="close"
    >
        <div v-if="isLoading" class="space-y-4 animate-pulse">
            <div class="grid grid-cols-2 gap-2">
                <div class="h-12 bg-gray-200 rounded"></div>
                <div class="h-12 bg-gray-200 rounded"></div>
                <div class="h-12 bg-gray-200 rounded"></div>
                <div class="h-12 bg-gray-200 rounded"></div>
                <div class="h-12 bg-gray-200 rounded"></div>
                <div class="h-12 bg-gray-200 rounded"></div>
            </div>
            <div class="mt-4">
                <div class="h-6 bg-gray-200 rounded w-1/4 mb-2"></div>
                <div class="h-32 bg-gray-200 rounded"></div>
            </div>
        </div>
        <div v-else-if="adjustment" class="space-y-4">
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div>
                    <p class="text-gray-500">Nomor Referensi</p>
                    <p class="font-bold">{{ adjustment.adjustment_number }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Status</p>
                    <span
                        class="badge"
                        :class="{
                            'badge-gray': adjustment.status === 'draft',
                            'badge-success': adjustment.status === 'approved',
                            'badge-danger': adjustment.status === 'rejected',
                            'badge-warning': adjustment.status === 'voided',
                        }"
                    >
                        {{ formatStatus(adjustment.status) }}
                    </span>
                </div>
                <div>
                    <p class="text-gray-500">Tanggal Dibuat</p>
                    <p>{{ formatDateTimeSimple(adjustment.created_at) }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Dibuat Oleh</p>
                    <p>{{ adjustment.creator?.name || '-' }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Outlet</p>
                    <p>{{ adjustment.outlet?.name || '-' }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Alasan</p>
                    <p class="capitalize">
                        {{ formatReason(adjustment.reason) }}
                    </p>
                </div>
                <div class="col-span-2" v-if="adjustment.notes">
                    <p class="text-gray-500">Catatan</p>
                    <p class="whitespace-pre-line">{{ adjustment.notes }}</p>
                </div>
            </div>

            <div class="mt-4">
                <h4 class="font-bold text-gray-700">Item Penyesuaian</h4>
                <div class="border rounded overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="p-3">Item</th>
                                <th class="p-3">Tipe</th>
                                <th class="p-3 text-right">Perubahan Qty</th>
                                <th
                                    class="p-3 text-right"
                                    v-if="
                                        adjustment.status === 'approved' ||
                                        adjustment.status === 'voided'
                                    "
                                >
                                    Stok Sebelum
                                </th>
                                <th
                                    class="p-3 text-right"
                                    v-if="
                                        adjustment.status === 'approved' ||
                                        adjustment.status === 'voided'
                                    "
                                >
                                    Stok Sesudah
                                </th>
                                <th class="p-3">Deskripsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in adjustment.items"
                                :key="item.id"
                                class="border-b last:border-b-0"
                            >
                                <td class="p-3">
                                    {{ item.inventory_item?.name }} ({{
                                        item.inventory_item?.uom?.name || '-'
                                    }})
                                </td>
                                <td class="p-3 capitalize">
                                    {{ item.movement_type }}
                                </td>
                                <td
                                    class="p-3 text-right font-bold"
                                    :class="
                                        item.qty_change > 0
                                            ? 'text-success'
                                            : 'text-danger'
                                    "
                                >
                                    {{ item.qty_change > 0 ? '+' : ''
                                    }}{{ item.qty_change_formatted }}
                                </td>
                                <td
                                    class="p-3 text-right"
                                    v-if="
                                        adjustment.status === 'approved' ||
                                        adjustment.status === 'voided'
                                    "
                                >
                                    {{ item.stock_before_formatted }}
                                </td>
                                <td
                                    class="p-3 text-right"
                                    v-if="
                                        adjustment.status === 'approved' ||
                                        adjustment.status === 'voided'
                                    "
                                >
                                    {{ item.stock_after_formatted }}
                                </td>
                                <td class="p-3">{{ item.description }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Approval Section -->
            <div
                v-if="adjustment.status === 'draft' && canApprove"
                class="bg-yellow-50 p-4 rounded border border-yellow-200 mt-4"
            >
                <h4 class="font-bold text-yellow-800 mb-2">
                    Tindakan Persetujuan
                </h4>
                <p class="text-sm text-yellow-700 mb-4">
                    Anda memiliki hak akses untuk menyetujui atau menolak
                    penyesuaian stok ini. Pastikan data sudah benar.
                </p>

                <div v-if="showRejectInput" class="mb-4">
                    <TextareaField
                        id="reject_notes"
                        v-model="rejectForm.notes"
                        label="Alasan Penolakan"
                        :class="{ 'is-invalid': rejectForm.errors.notes }"
                        :feedback="rejectForm.errors.notes"
                        required
                    />
                </div>

                <div class="flex gap-2">
                    <template v-if="!showRejectInput">
                        <button
                            class="btn btn-success"
                            @click="approve"
                            :disabled="isProcessing"
                        >
                            <FontAwesomeIcon :icon="faCheck" /> Setujui
                        </button>
                        <button
                            class="btn btn-danger"
                            @click="showRejectInput = true"
                            :disabled="isProcessing"
                        >
                            <FontAwesomeIcon :icon="faTimes" /> Tolak
                        </button>
                    </template>
                    <template v-else>
                        <button
                            class="btn btn-danger"
                            @click="reject"
                            :disabled="rejectForm.processing"
                        >
                            Konfirmasi Tolak
                        </button>
                        <button
                            class="btn btn-flat"
                            @click="showRejectInput = false"
                            :disabled="rejectForm.processing"
                        >
                            Batal
                        </button>
                    </template>
                </div>
            </div>

            <div
                v-if="adjustment.status === 'approved' && canVoid"
                class="mt-6 flex justify-end"
            >
                <button
                    class="btn btn-outline btn-danger"
                    @click="voidAdjustment"
                    :disabled="isProcessing"
                >
                    <FontAwesomeIcon :icon="faBan" /> Batalkan Penyesuaian
                    (Void)
                </button>
            </div>
        </div>

        <template #footer>
            <button
                type="button"
                class="btn btn-flat"
                @click="close"
                :disabled="isProcessing"
            >
                Tutup
            </button>
        </template>
    </PopUpPage>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import { faCheck, faTimes, faBan } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import PopUpPage from '@/Components/UI/PopUpPage.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';
import { formatDateTimeSimple } from '@/Composable/date.js';

const page = usePage();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    adjustment: {
        type: Object,
        default: null,
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const isProcessing = ref(false);
const showRejectInput = ref(false);

const rejectForm = useForm({
    notes: '',
});

const can = (permission) => {
    return (
        page.props.auth.permissions.includes(permission) ||
        page.props.auth.permissions.includes('inventory.*')
    );
};

const canApprove = computed(() => can('inventory.adjustment.approve'));
const canVoid = computed(() => can('inventory.adjustment.void'));

const close = () => {
    showRejectInput.value = false;
    rejectForm.reset();
    emit('close');
};

const approve = () => {
    if (
        confirm(
            'Apakah Anda yakin ingin menyetujui penyesuaian ini? Stok akan diperbarui.',
        )
    ) {
        isProcessing.value = true;
        router.post(
            route('inventory.adjustments.approve', props.adjustment.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    isProcessing.value = false;
                    close();
                },
            },
        );
    }
};

const reject = () => {
    rejectForm.post(
        route('inventory.adjustments.reject', props.adjustment.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                close();
            },
        },
    );
};

const voidAdjustment = () => {
    if (
        confirm(
            'Apakah Anda yakin ingin membatalkan (VOID) penyesuaian ini? Stok akan dikembalikan ke sebelum penyesuaian.',
        )
    ) {
        isProcessing.value = true;
        router.post(
            route('inventory.adjustments.void', props.adjustment.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    isProcessing.value = false;
                    close();
                },
            },
        );
    }
};

const formatStatus = (status) => {
    const map = {
        draft: 'Draf',
        approved: 'Disetujui',
        rejected: 'Ditolak',
        voided: 'Dibatalkan',
    };
    return map[status] || status;
};

const formatReason = (reason) => {
    const map = {
        waste: 'Rusak / Terbuang',
        expired: 'Kedaluwarsa',
        lost: 'Hilang',
        correction: 'Koreksi',
        production: 'Produksi',
        other: 'Lainnya',
    };
    return map[reason] || reason;
};
</script>
