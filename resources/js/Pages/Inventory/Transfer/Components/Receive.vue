<template>
    <PopUpPage :class="{ show: show }" :title="isApprove ? 'Setujui Transfer (Kirim)' : 'Terima Transfer'" size="max-w-3xl" @close="close">
        <form @submit.prevent="submit" class="p-4 space-y-4">
            <div v-if="transfer" class="mb-4 bg-gray-50 p-4 rounded-lg">
                <p><strong>No Transfer:</strong> {{ transfer.transfer_number }}</p>
                <p><strong>Dari Outlet:</strong> {{ transfer.from_outlet?.name }}</p>
                <p><strong>Ke Outlet:</strong> {{ transfer.to_outlet?.name }}</p>
            </div>
            
            <div class="border-t pt-4">
                <h3 class="text-lg font-semibold mb-4">Detail Item</h3>
                
                <div v-if="form.items.length === 0" class="text-center py-4 text-gray-500 border rounded-lg">
                    Data item tidak ditemukan.
                </div>
                
                <div v-else class="space-y-3">
                    <div v-for="(item, index) in form.items" :key="index" class="flex gap-4 items-center border p-3 rounded-lg">
                        <div class="flex-1">
                            <div class="font-semibold">{{ item.name }}</div>
                            <div class="text-sm text-gray-500">Dikirim: {{ item.qty_sent_formatted }}</div>
                        </div>
                        <div class="w-40" v-if="!isApprove">
                            <TextField
                                v-model="item.qty_received"
                                type="number"
                                label="Jml Diterima"
                                min="0"
                                :max="item.qty_sent"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <template #footer>
            <button type="button" class="btn btn-flat" @click="close" :disabled="form.processing">
                Batal
            </button>
            <button type="button" class="btn btn-main" @click="submit" :disabled="form.processing || form.items.length === 0">
                {{ isApprove ? 'Setujui & Kirim' : 'Konfirmasi Penerimaan' }}
            </button>
        </template>
    </PopUpPage>
</template>

<script setup>
import { watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PopUpPage from '@/Components/UI/PopUpPage.vue';
import TextField from '@/Components/Form/TextField.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    transfer: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    items: [],
});

const isApprove = computed(() => {
    return props.transfer?.status === 'pending';
});

watch(
    () => props.transfer,
    (data) => {
        form.reset();
        if (data && data.items) {
            form.items = data.items.map(i => ({
                id: i.id,
                inventory_item_id: i.inventory_item_id,
                name: i.inventory_item?.name || 'Unknown',
                qty_sent: i.qty_transferred,
                qty_received: i.qty_transferred, // default to receive all
            }));
        } else {
            form.items = [];
        }
    },
    { immediate: true }
);

const close = () => {
    form.clearErrors();
    emit('close');
};

const submit = () => {
    if (props.transfer?.id) {
        const actionRoute = isApprove.value ? 'inventory.transfers.approve' : 'inventory.transfers.receive';
        form.post(route(actionRoute, props.transfer.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => close(),
        });
    }
};
</script>
