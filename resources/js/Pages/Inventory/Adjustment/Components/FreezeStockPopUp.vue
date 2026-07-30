<template>
    <PopUpPage
        :class="{ show: show }"
        title="Kelola Pembekuan Stok"
        @close="close"
    >
        <div class="space-y-2">
            <p class="text-sm text-gray-600 mb-4">
                Pembekuan stok akan memblokir fitur penyesuaian stok pada outlet
                yang dipilih hingga stok dicairkan kembali. Fitur ini berguna
                saat proses Stock Opname sedang berlangsung.
            </p>

            <div class="space-y-2 rounded overflow-hidden">
                <div
                    v-for="outlet in outlets"
                    :key="outlet.id"
                    class="p-3 border rounded-lg flex justify-between items-center"
                    :class="
                        outlet.is_stock_frozen ? 'bg-red-50' : 'bg-green-50'
                    "
                >
                    <div>
                        <p class="font-bold">{{ outlet.name }}</p>
                        <p
                            class="text-xs"
                            :class="
                                outlet.is_stock_frozen
                                    ? 'text-danger'
                                    : 'text-success'
                            "
                        >
                            <FontAwesomeIcon
                                :icon="
                                    outlet.is_stock_frozen ? faLock : faUnlock
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
                    <div>
                        <button
                            v-if="!outlet.is_stock_frozen"
                            class="btn btn-sm btn-outline btn-danger"
                            @click="freeze(outlet)"
                            :disabled="isProcessing"
                        >
                            Bekukan
                        </button>
                        <button
                            v-else
                            class="btn btn-sm btn-outline btn-success"
                            @click="unfreeze(outlet)"
                            :disabled="isProcessing"
                        >
                            Cairkan
                        </button>
                    </div>
                </div>
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
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { faLock, faUnlock } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import PopUpPage from '@/Components/UI/PopUpPage.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    outlets: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);
const isProcessing = ref(false);

const close = () => {
    emit('close');
};

const freeze = (outlet) => {
    if (
        confirm(
            `Apakah Anda yakin ingin membekukan stok pada outlet ${outlet.name}?`,
        )
    ) {
        isProcessing.value = true;
        router.post(
            route('inventory.adjustments.freeze'),
            { outlet_id: outlet.id },
            {
                preserveScroll: true,
                onFinish: () => {
                    isProcessing.value = false;
                },
            },
        );
    }
};

const unfreeze = (outlet) => {
    if (
        confirm(
            `Apakah Anda yakin ingin mencairkan stok pada outlet ${outlet.name}?`,
        )
    ) {
        isProcessing.value = true;
        router.post(
            route('inventory.adjustments.unfreeze'),
            { outlet_id: outlet.id },
            {
                preserveScroll: true,
                onFinish: () => {
                    isProcessing.value = false;
                },
            },
        );
    }
};
</script>
