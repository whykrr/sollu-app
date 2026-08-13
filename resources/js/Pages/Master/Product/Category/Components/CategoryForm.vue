<template>
    <div>
        <div class="space-y-2">
            <TextField
                v-model="form.name"
                label="Nama Kategori"
                placeholder="Masukkan nama kategori"
                :error="form.errors.name"
                required
            />

            <!-- Only show Parent select if editing a child, or creating a new category -->
            <!-- We disable parent selection if this category already has children (checked in backend, but good to disable in UI too) -->
            <DropdownField
                v-if="!hasChildren"
                v-model="form.parent_id"
                label="Kategori Induk (Opsional)"
                :options="availableParentsFormatted"
                placeholder="-- Tidak Ada (Kategori Utama) --"
                :error="form.errors.parent_id"
            />

            <NumberField
                v-model="form.sort_order"
                label="Urutan (Opsional)"
                placeholder="Contoh: 1"
                :error="form.errors.sort_order"
            />
        </div>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-end gap-2 w-full">
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    @click="closeForm"
                >
                    Batal
                </button>
                <button
                    type="button"
                    class="btn btn-highlight-main"
                    :disabled="form.processing"
                    @click="submit"
                >
                    Simpan
                </button>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, watch, ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TextField from '@/Components/Form/TextField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import NumberField from '@/Components/Form/NumberField.vue';

const props = defineProps({
    category: {
        type: Object,
        default: null,
    },
    parentCategory: {
        type: Object,
        default: null,
    },
    allCategories: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    parent_id: '',
    sort_order: '',
});

const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});

watch(
    () => props.category,
    () => {
        form.clearErrors();
        if (props.category) {
            form.name = props.category.name;
            form.parent_id = props.category.parent_id || '';
            form.sort_order =
                props.category.sort_order !== null
                    ? String(props.category.sort_order)
                    : '';
        } else {
            form.name = '';
            form.parent_id = props.parentCategory
                ? props.parentCategory.id
                : '';
            form.sort_order = '';
        }
    },
    { immediate: true },
);

// Only root categories can be parents, and a category cannot be its own parent
const availableParents = computed(() => {
    return props.allCategories.filter((c) => {
        // Can't be parent if it already has a parent (max 1 level depth)
        if (c.parent_id) return false;
        // Can't be itself
        if (props.category && c.id === props.category.id) return false;
        return true;
    });
});

const availableParentsFormatted = computed(() => {
    return availableParents.value.map((c) => ({
        value: c.id,
        label: c.name,
    }));
});

const hasChildren = computed(() => {
    if (!props.category) return false;
    return props.category.children && props.category.children.length > 0;
});

const closeForm = () => {
    emit('close');
};

const submit = () => {
    if (props.category) {
        form.put(route('master.categories.update', props.category.id), {
            onSuccess: () => closeForm(),
        });
    } else {
        form.post(route('master.categories.store'), {
            onSuccess: () => closeForm(),
        });
    }
};
</script>
