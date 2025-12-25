<script setup>
import { Link } from '@inertiajs/vue3';
import TextField from '@/Components/Form/TextField.vue';

const props = defineProps({
    form: Object,
    categories: Array,
    isEditing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['submit']);
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 gap-4">
            <TextField
                id="name"
                v-model="form.name"
                label="Nama Kategori"
                placeholder="Masukan nama kategori..."
                :class="{ 'is-invalid': form.errors.name }"
                :feedback="form.errors.name"
            />

            <div>
                <label
                    for="parent_id"
                    class="block mb-1 text-sm font-medium text-neutral-700"
                    >Parent Kategori</label
                >
                <select
                    id="parent_id"
                    v-model="form.parent_id"
                    class="form w-full"
                >
                    <option :value="null">-- Tidak Ada Parent --</option>
                    <option
                        v-for="cat in categories"
                        :key="cat.id"
                        :value="cat.id"
                    >
                        {{ cat.name }}
                    </option>
                </select>
                <p class="mt-1 text-xs text-gray-500">
                    Pilih parent jika ini adalah sub-kategori. Maksimal 3 level.
                </p>
                <span
                    v-if="form.errors.parent_id"
                    class="text-xs text-red-600"
                    >{{ form.errors.parent_id }}</span
                >
            </div>
        </div>

        <div
            class="flex items-center justify-end space-x-3 pt-4 border-t border-neutral-100"
        >
            <Link
                :href="route('products.categories.index')"
                class="btn bg-white border border-neutral-200 text-neutral-600 hover:bg-neutral-50"
                >Batal</Link
            >
            <button
                type="submit"
                :disabled="form.processing"
                class="btn btn-primary"
                @click="emit('submit')"
            >
                {{ isEditing ? 'Update' : 'Simpan' }}
            </button>
        </div>
    </div>
</template>
