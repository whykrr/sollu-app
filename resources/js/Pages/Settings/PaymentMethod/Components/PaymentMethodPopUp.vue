<template>
    <form class="space-y-2" @submit.prevent="submit">
        <TextField
            v-model="form.name"
            label="Nama Metode Pembayaran"
            placeholder="Contoh: QRIS BCA, Transfer Bank Mandiri, EDC BCA"
            :feedback="form.errors.name"
            required
        />

        <DropdownField
            v-model="form.type"
            label="Jenis Pembayaran"
            placeholder="Pilih jenis pembayaran"
            :options="types"
            :feedback="form.errors.type"
            required
        />

        <div class="space-y-1 pt-1">
            <SelectionGroupField
                v-model="form.outlet_ids"
                label="Aktivasi di Outlet / Cabang"
                :options="outletOptions"
                multiple
                show-select-all
                :feedback="form.errors.outlet_ids"
            />
            <p class="text-xs text-neutral-500">
                Pilih outlet yang dapat menerima transaksi dengan metode pembayaran ini.
            </p>
        </div>



        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex items-center justify-end w-full gap-2">
                <button
                    type="button"
                    class="btn btn-flat"
                    @click="popUpStore.close()"
                >
                    Batal
                </button>
                <button
                    type="button"
                    class="btn btn-highlight-main"
                    :disabled="form.processing"
                    @click="submit"
                >
                    {{ paymentMethod ? 'Simpan Perubahan' : 'Tambah Metode' }}
                </button>
            </div>
        </Teleport>
    </form>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePopUpStore } from '@/store/popup';
import TextField from '@/Components/Form/TextField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue';

const props = defineProps({
    paymentMethod: {
        type: Object,
        default: null,
    },
    outlets: {
        type: Array,
        default: () => [],
    },
    types: {
        type: Array,
        default: () => [],
    },
});

const popUpStore = usePopUpStore();
const isMounted = ref(false);

const outletOptions = computed(() => {
    return props.outlets.map((o) => ({
        value: o.id,
        label: o.name,
    }));
});

// Calculate initial selected outlet IDs
const getInitialSelectedOutlets = () => {
    if (!props.paymentMethod) {
        // Default: select all outlets for new payment method
        return props.outlets.map((o) => o.id);
    }

    if (props.paymentMethod.outlets && props.paymentMethod.outlets.length > 0) {
        return props.paymentMethod.outlets
            .filter((o) => o.pivot ? o.pivot.is_enabled : true)
            .map((o) => o.id);
    }

    // If no specific pivot data exists, fallback to all outlets
    return props.outlets.map((o) => o.id);
};

const form = useForm({
    name: props.paymentMethod?.name || '',
    type: props.paymentMethod?.type || (props.types[0]?.value || 'cash'),
    outlet_ids: getInitialSelectedOutlets(),
});

const submit = () => {
    if (props.paymentMethod) {
        form.put(route('settings.payment-methods.update', props.paymentMethod.id), {
            preserveScroll: true,
            onSuccess: () => {
                popUpStore.close();
            },
        });
    } else {
        form.post(route('settings.payment-methods.store'), {
            preserveScroll: true,
            onSuccess: () => {
                popUpStore.close();
            },
        });
    }
};

onMounted(() => {
    isMounted.value = true;
});
</script>
