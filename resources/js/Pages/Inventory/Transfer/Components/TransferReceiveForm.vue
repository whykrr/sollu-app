<template>
    <div>
        <form @submit.prevent="submit" class="space-y-2">
            <div v-if="transferData" class="mb-4 bg-gray-50 p-4 rounded-lg">
                <p>
                    <strong>No Transfer:</strong>
                    {{ transferData.transfer_number }}
                </p>
                <p>
                    <strong>Dari Outlet:</strong>
                    {{
                        transferData.from_outlet?.name ||
                        transferData.fromOutlet?.name ||
                        '-'
                    }}
                </p>
                <p>
                    <strong>Ke Outlet:</strong>
                    {{
                        transferData.to_outlet?.name ||
                        transferData.toOutlet?.name ||
                        '-'
                    }}
                </p>
            </div>

            <div class="border-t pt-4">
                <h3 class="text-lg font-semibold mb-2">
                    Detail Penerimaan Item
                </h3>

                <div
                    v-if="form.items.length === 0"
                    class="text-center py-4 text-gray-500 border rounded-lg"
                >
                    Data item tidak ditemukan.
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="(item, index) in form.items"
                        :key="index"
                        class="flex gap-2 items-center border p-2 rounded-lg"
                    >
                        <div class="flex-1">
                            <div class="font-semibold">{{ item.name }}</div>
                            <div class="text-sm text-gray-500">
                                Dikirim: {{ item.qty_sent_formatted }}
                                {{ item.uom_name }}
                            </div>
                        </div>
                        <div class="w-40">
                            <NumberField
                                v-model="item.qty_received"
                                label="Jml Diterima"
                                min="0"
                                :max="item.qty_sent"
                                step="0.01"
                            />
                        </div>
                        <div class="w-24 text-right">
                            <div class="text-xs text-gray-500">Selisih</div>
                            <div
                                class="font-semibold"
                                :class="{
                                    'text-danger':
                                        item.qty_sent - item.qty_received > 0,
                                }"
                            >
                                {{
                                    formatNumber(
                                        item.qty_sent - item.qty_received,
                                    )
                                }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <button
                type="button"
                class="btn btn-flat"
                @click="close"
                :disabled="form.processing"
            >
                Batal
            </button>
            <button
                type="button"
                class="btn btn-main"
                @click="submit"
                :disabled="form.processing || form.items.length === 0"
            >
                Konfirmasi Penerimaan
            </button>
        </Teleport>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePopUpStore } from '@/store/popup';
import NumberField from '@/Components/Form/NumberField.vue';

const props = defineProps({
    transferData: Object,
});

const emit = defineEmits(['refresh']);
const popUpStore = usePopUpStore();
const isMounted = ref(false);

const form = useForm({
    items: [],
});

onMounted(() => {
    isMounted.value = true;
    if (props.transferData?.items) {
        form.items = props.transferData.items.map((i) => ({
            id: i.id,
            name:
                i.inventory_item?.name ||
                i.inventoryItem?.name ||
                'Unknown',
            uom_name:
                i.inventory_item?.uom?.name ||
                i.inventoryItem?.uom?.name ||
                '',
            qty_sent: i.qty, // original value for math and limits
            qty_sent_formatted: i.qty_formatted,
            qty_received: i.qty_formatted, // default to receive all, properly formatted
        }));
    } else {
        form.items = [];
    }
});

const close = () => {
    form.clearErrors();
    popUpStore.close();
};

const submit = () => {
    if (props.transferData?.id) {
        form.post(route('inventory.transfers.receive', props.transferData.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                close();
                emit('refresh');
            },
        });
    }
};

const formatNumber = (num) => {
    return Number(num || 0).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
};
</script>
