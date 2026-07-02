<template>
    <PopUpPage title="Filter Produk" size="sm" @close="$emit('close')">
        <div class="p-3">
            <div class="space-y-4">
                <DropdownField
                    v-model="form.category_id"
                    label="Kategori"
                    :options="categories"
                />
                
                <DropdownField
                    v-model="form.product_type"
                    label="Tipe Produk"
                    :options="[
                        { value: 'simple', label: 'Simple' },
                        { value: 'variant', label: 'Variant' },
                        { value: 'recipe', label: 'Recipe (BOM)' },
                        { value: 'bundle', label: 'Bundle / Paket' },
                        { value: 'service', label: 'Layanan / Service' },
                    ]"
                />

                <DropdownField
                    v-model="form.status"
                    label="Status"
                    :options="[
                        { value: 'active', label: 'Aktif' },
                        { value: 'archived', label: 'Diarsipkan' },
                    ]"
                />
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between w-full">
                <button type="button" class="btn btn-outline-main btn-sm" @click="reset">
                    Reset
                </button>
                <button type="button" class="btn btn-highlight-main btn-sm" @click="apply">
                    Terapkan Filter
                </button>
            </div>
        </template>
    </PopUpPage>
</template>

<script setup>
import { ref } from 'vue';
import PopUpPage from '@/Components/UI/PopUpPage.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';

const emit = defineEmits(['close', 'apply']);

const categories = [
    { value: 1, label: 'Makanan Utama' },
    { value: 2, label: 'Minuman Dingin' },
    { value: 3, label: 'Snack' },
];

const form = ref({
    category_id: '',
    product_type: '',
    status: '',
});

const apply = () => {
    emit('apply', form.value);
    emit('close');
};

const reset = () => {
    form.value = { category_id: '', product_type: '', status: '' };
};
</script>
