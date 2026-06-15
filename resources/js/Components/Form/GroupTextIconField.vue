<template>
    <div class="form-group has-text">
        <label v-if="icon" :for="id" class="form-group-text">
            <FontAwesomeIcon :icon="icon" />
        </label>

        <input
            type="text"
            :value="modelValue"
            class="form"
            v-bind="$attrs"
            @input="$emit('update:modelValue', $event.target.value)"
        />
    </div>
</template>

<script setup>
import { ref, toRefs } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    icon: { type: [Array, Object], default: null },
    inputClass: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);
</script>
