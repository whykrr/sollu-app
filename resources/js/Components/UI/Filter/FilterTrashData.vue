<script setup>
import { faTrash } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineProps({
    modelValue: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['update:modelValue']);

const onChange = (event) => {
    emit('update:modelValue', event.target.checked ? 1 : 0);
};
</script>

<template>
    <div class="form-check">
        <input
            id="deleted_data"
            type="checkbox"
            class="form-check-input"
            name="filter_trash"
            :checked="Number(modelValue) === 1"
            @change="onChange"
        />
        <label class="form-check-label" for="deleted_data">Data Terhapus</label>
    </div>
</template>
