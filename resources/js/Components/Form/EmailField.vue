<template>
    <div>
        <label
            v-if="label"
            :for="$attrs.id"
            class="label"
            :class="{ '!text-xs': size === 'sm', '!text-base': size === 'lg' }"
        >
            {{ label }}
        </label>
        <input
            type="email"
            class="form"
            :class="[
                { sm: size === 'sm', lg: size === 'lg', adaptive: size === 'adaptive' },
                { 'is-invalid': error, 'is-valid': success },
            ]"
            :value="modelValue"
            v-bind="$attrs"
            @input="emit('update:modelValue', $event.target.value)"
        />
        <span v-if="error" class="form-feedback text-danger">{{ error }}</span>
        <span v-else-if="success" class="form-feedback text-success">{{ success }}</span>
        <span v-else-if="feedback" class="form-feedback text-neutral-500">{{ feedback }}</span>
    </div>
</template>
<script setup>
defineOptions({
    inheritAttrs: false,
})

const props = defineProps({
    label: String,
    feedback: String,
    error: String,
    success: String,
    modelValue: String,
    size: {
        type: String,
        default: 'base',
        validator: v => ['sm', 'base', 'adaptive', 'lg'].includes(v),
    },
})
const emit = defineEmits(['update:modelValue'])
</script>
