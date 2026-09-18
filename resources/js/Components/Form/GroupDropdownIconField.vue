<template>
    <div class="form-group has-text" :class="{ sm: size === 'sm', lg: size === 'lg' }">
        <label v-if="icon" :for="$attrs.id" class="form-group-text">
            <FontAwesomeIcon
                :icon="icon"
                :class="{ 'text-xs': size === 'sm', 'text-base': size === 'lg' }"
            />
        </label>

        <select
            :value="modelValue"
            :class="['form', { sm: size === 'sm', lg: size === 'lg' }, inputClass]"
            v-bind="$attrs"
            style="padding-right: 2.5rem !important"
            @change="handleChange"
        >
            <option v-if="placeholder" value="">
                {{ placeholder }}
            </option>
            <option v-for="(option, index) in options" :key="index" :value="option.value">
                {{ option.label }}
            </option>
        </select>
    </div>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

defineOptions({
    inheritAttrs: false,
})

defineProps({
    modelValue: { type: [String, Number], default: '' },
    placeholder: { type: String, default: '' },
    icon: { type: [Array, Object], default: null },
    inputClass: { type: String, default: '' },
    options: {
        type: Array,
        default: () => [], // format: [{ value: '1', label: 'Satu' }]
    },
    size: {
        type: String,
        default: 'base',
        validator: v => ['sm', 'base', 'lg'].includes(v),
    },
})

const emit = defineEmits(['update:modelValue', 'change'])

const handleChange = event => {
    const value = event.target.value
    emit('update:modelValue', value)
    emit('change', value)
}
</script>
