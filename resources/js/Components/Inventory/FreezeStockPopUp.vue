<template>
    <div>
        <div class="space-y-2">
            <p v-if="description" class="text-sm text-gray-600">
                {{ description }}
            </p>

            <div v-if="isLoading" class="text-center py-4">
                <span
                    class="animate-spin inline-block mr-2 border-2 border-t-2 border-t-transparent border-gray-600 rounded-full w-4 h-4"
                ></span>
                Memuat data outlet...
            </div>

            <div v-else class="space-y-2">
                <div
                    v-if="hasFrozenOutlet"
                    class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm p-3 rounded-lg flex items-start gap-2"
                >
                    <FontAwesomeIcon
                        :icon="faInfoCircle"
                        class="mt-0.5 text-yellow-500"
                    />
                    <p>
                        Beberapa outlet saat ini sedang dibekukan stoknya.
                        Transaksi terkait stok pada outlet tersebut tidak dapat
                        dilakukan hingga dicairkan.
                    </p>
                </div>

                <div class="border rounded-lg overflow-hidden bg-white">
                    <div
                        v-for="outlet in outlets"
                        :key="outlet.id"
                        class="p-4 border-b last:border-b-0 flex justify-between items-center transition-colors"
                        :class="
                            outlet.is_stock_frozen
                                ? 'bg-red-50/50'
                                : 'hover:bg-gray-50'
                        "
                    >
                        <div>
                            <p class="font-bold text-gray-800">
                                {{ outlet.name }}
                            </p>
                            <p
                                class="text-xs mt-1"
                                :class="
                                    outlet.is_stock_frozen
                                        ? 'text-danger font-medium'
                                        : 'text-success font-medium'
                                "
                            >
                                <FontAwesomeIcon
                                    :icon="
                                        outlet.is_stock_frozen
                                            ? faLock
                                            : faUnlock
                                    "
                                    class="mr-1"
                                />
                                {{
                                    outlet.is_stock_frozen
                                        ? 'Stok Dibekukan'
                                        : 'Stok Cair'
                                }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                v-if="processingId === outlet.id"
                                class="text-xs text-gray-500 font-medium"
                            >
                                Menyimpan...
                            </span>
                            <Switch
                                :id="'freeze-switch-' + outlet.id"
                                :model-value="outlet.is_stock_frozen ? 1 : 0"
                                :disabled="isProcessing || processingId === outlet.id"
                                @update:model-value="
                                    (val) => toggleFreeze(outlet, val)
                                "
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <button
                type="button"
                class="btn btn-flat"
                :disabled="isProcessing"
                @click="close"
            >
                Tutup
            </button>
        </Teleport>
    </div>

    <Modal
        :show="showConfirm"
        :title="confirmTitle"
        @close="closeConfirm"
    >

        <p class="text-gray-600">{{ confirmMessage }}</p>
        <template #footer>
            <button type="button" class="btn btn-flat" :disabled="isProcessing" @click="closeConfirm">
                Batal
            </button>
            <button
                type="button"
                class="btn"
                :class="confirmActionType === 'freeze' ? 'btn-danger' : 'btn-success'"
                :disabled="isProcessing"
                @click="executeToggle"
            >
                Ya, Lanjutkan
            </button>
        </template>
    </Modal>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    faLock,
    faUnlock,
    faInfoCircle,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Modal from '@/Components/Notifications/Modal.vue';
import Switch from '@/Components/Form/Switch.vue';
import { useOutlets } from '@/Composable/useOutlets.js';

const props = defineProps({
    title: {
        type: String,
        default: 'Kelola Pembekuan Stok',
    },
    description: {
        type: String,
        default:
            'Pembekuan stok akan memblokir fitur penyesuaian stok pada outlet yang dipilih hingga stok dicairkan kembali.',
    },
});

const emit = defineEmits(['close']);
const isProcessing = ref(false);
const processingId = ref(null);

const showConfirm = ref(false);
const confirmTitle = ref('');
const confirmMessage = ref('');
const confirmActionType = ref('');
const pendingOutlet = ref(null);
const pendingValue = ref(null);

const { outlets, isLoading, fetchOutlets } = useOutlets();

const hasFrozenOutlet = computed(() => {
    return outlets.value.some((o) => o.is_stock_frozen);
});

const isMounted = ref(false);

onMounted(() => {
    isMounted.value = true;
    fetchOutlets();
});

const close = () => {
    emit('close');
};

const closeConfirm = () => {
    showConfirm.value = false;
    pendingOutlet.value = null;
    pendingValue.value = null;
};

const toggleFreeze = (outlet, newValue) => {
    const actionText = newValue ? 'membekukan' : 'mencairkan';

    confirmTitle.value = newValue ? 'Konfirmasi Pembekuan' : 'Konfirmasi Pencairan';
    confirmMessage.value = `Apakah Anda yakin ingin ${actionText} stok pada outlet ${outlet.name}?`;
    confirmActionType.value = newValue ? 'freeze' : 'unfreeze';
    
    pendingOutlet.value = outlet;
    pendingValue.value = newValue;
    showConfirm.value = true;
};

const executeToggle = () => {
    if (!pendingOutlet.value) return;
    
    const outlet = pendingOutlet.value;
    const newValue = pendingValue.value;

    isProcessing.value = true;
    processingId.value = outlet.id;
    showConfirm.value = false;

    const routeName = newValue
        ? 'inventory.outlets.freeze'
        : 'inventory.outlets.unfreeze';

    router.post(
        route(routeName),
        { outlet_id: outlet.id },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                fetchOutlets(true);
            },
            onFinish: () => {
                isProcessing.value = false;
                processingId.value = null;
                pendingOutlet.value = null;
                pendingValue.value = null;
            },
        },
    );
};
</script>
