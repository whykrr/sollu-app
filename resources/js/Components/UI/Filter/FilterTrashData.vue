<script setup>
import { faTrash } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

defineProps({
    modelValue: {
        type: [Number, String, Boolean],
        default: 0,
    },
})

const emit = defineEmits(['update:modelValue'])

const onChange = event => {
    emit('update:modelValue', event.target.checked ? 1 : 0)
}
</script>

<template>
    <div class="form-check sm">
        <input
            id="deleted_data"
            type="checkbox"
            name="filter_trash"
            :checked="Number(modelValue) === 1"
            @change="onChange"
        />
        <label class="flex items-center gap-1" for="deleted_data">
            <FontAwesomeIcon :icon="faTrash" class="text-xs text-neutral-400" />
            <span>Data Terhapus</span>
        </label>
    </div>
</template>
