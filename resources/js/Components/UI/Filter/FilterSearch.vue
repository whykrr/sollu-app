<template>
    <div class="relative flex items-center select-none" :class="widthClass">
        <span
            class="absolute left-2.5 top-1/2 -translate-y-1/2 text-neutral-400 text-xs pointer-events-none z-10 flex items-center justify-center"
        >
            <FontAwesomeIcon :icon="faSearch" />
        </span>

        <input
            :id="id"
            type="text"
            :value="modelValue"
            class="form sm"
            style="padding-left: 1.7rem !important; padding-right: 1.75rem !important"
            :placeholder="placeholder"
            @input="$emit('update:modelValue', $event.target.value)"
        />

        <button
            v-if="modelValue"
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600 text-xs cursor-pointer p-0.5 z-10 flex items-center justify-center"
            @click="clear"
        >
            <FontAwesomeIcon :icon="faTimes" />
        </button>
    </div>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSearch, faTimes } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Cari...',
    },
    id: {
        type: String,
        default: 'filter_search',
    },
    widthClass: {
        type: String,
        default: 'w-full sm:w-56 md:w-64',
    },
})

const emit = defineEmits(['update:modelValue', 'clear'])

const clear = () => {
    emit('update:modelValue', '')
    emit('clear')
}
</script>
