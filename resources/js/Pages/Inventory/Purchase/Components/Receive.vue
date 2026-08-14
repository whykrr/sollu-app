<template>
    <form class="space-y-2" @submit.prevent="submit">
        <div v-if="purchase" class="mb-2 bg-gray-100 p-4 rounded-lg">
            <p><strong>Supplier:</strong> {{ purchase.supplier?.name }}</p>
            <p><strong>Outlet:</strong> {{ purchase.outlet?.name }}</p>
        </div>

        <div class="border-t pt-2">
            <h3 class="text-lg font-semibold mb-2">
                Input Penerimaan & Konversi
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
                    class="flex gap-2 items-center border p-3 rounded-lg"
                >
                    <div class="flex-1">
                        <div class="font-semibold">{{ item.name }}</div>
                        <div class="text-sm text-gray-500">
                            Dipesan: {{ item.qty_ordered }}
                            {{ item.uom_name }}
                        </div>
                        <div
                            v-if="
                                item.uom_name &&
                                item.base_uom_name &&
                                item.uom_name !== item.base_uom_name
                            "
                            class="text-xs text-blue-600 mt-1"
                        >
                            Konversi: 1 {{ item.uom_name }} =
                            {{ item.conversion_factor ?? '1' }}
                            {{ item.base_uom_name }}
                        </div>
                    </div>
                    <div class="w-32">
                        <NumberField
                            v-model="item.qty_ordered"
                            label="Jml Diterima"
                            class="sm"
                            :class="{
                                'is-invalid':
                                    form.errors[`items.${index}.qty_received`],
                            }"
                            :error="form.errors[`items.${index}.qty_received`]"
                        />
                    </div>
                    <div class="w-32">
                        <NumberField
                            v-model="item.conversion_factor"
                            label="Faktor Konversi"
                            min="1"
                            class="sm"
                            step="any"
                            :class="{
                                'is-invalid':
                                    form.errors[
                                        `items.${index}.conversion_factor`
                                    ],
                            }"
                            :error="
                                form.errors[`items.${index}.conversion_factor`]
                            "
                            title="Faktor pengali ke satuan inventori (contoh: 1 dus = 24 botol, isi 24)"
                        />
                    </div>
                    <div class="w-32 pt-6 text-sm text-gray-700">
                        Masuk Stok:
                        <strong
                            >{{
                                new Intl.NumberFormat('id-ID', {
                                    maximumFractionDigits: 2,
                                }).format(
                                    Number(item.qty_received || 0) *
                                        Number(item.conversion_factor || 1),
                                )
                            }}
                            {{ item.base_uom_name }}</strong
                        >
                    </div>
                </div>
            </div>
        </div>
    </form>

    <Teleport v-if="isMounted" to="#popUpFooter">
        <button
            type="button"
            class="btn btn-flat"
            :disabled="form.processing"
            @click="close"
        >
            Batal
        </button>
        <button
            type="button"
            class="btn btn-main"
            :disabled="form.processing || form.items.length === 0"
            @click="submit"
        >
            Simpan Penerimaan
        </button>
    </Teleport>
</template>

<script setup>
import { watch, ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePopUpStore } from '@/store/popup';
import TextField from '@/Components/Form/TextField.vue';
import NumberField from '@/Components/Form/NumberField.vue';

const popUpStore = usePopUpStore();

const props = defineProps({
    purchase: {
        type: Object,
        default: null,
    },
});

const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});

const form = useForm({
    items: [],
});

watch(
    () => props.purchase,
    (data) => {
        form.reset();
        if (data && data.items) {
            form.items = data.items.map((i) => ({
                id: i.id,
                inventory_item_id: i.inventory_item_id,
                name: i.inventory_item?.name || 'Unknown',
                uom_name: i.uom?.code || '-',
                base_uom_name: i.inventory_item?.uom?.code || '-',
                qty_ordered: i.qty_ordered_formatted,
                qty_received: i.qty_ordered_formatted, // default to fully received
                conversion_factor: 1, // default conversion factor 1
            }));
        } else {
            form.items = [];
        }
    },
    { immediate: true },
);

const close = () => {
    form.clearErrors();
    popUpStore.close();
};

const submit = () => {
    if (props.purchase?.id) {
        form.post(route('inventory.purchases.receive', props.purchase.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => close(),
        });
    }
};
</script>
