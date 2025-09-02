<template>
    <div class="form-group has-text">
        <label v-if="icon" :for="$attrs.id" class="form-group-text">
            <FontAwesomeIcon :icon="icon" />
        </label>

        <select
            :value="modelValue"
            class="pr-10"
            :class="['form', inputClass]"
            @change="$emit('update:modelValue', $event.target.value)"
            v-bind="$attrs"
        >
            <option v-if="placeholder" value="">
                {{ placeholder }}
            </option>
            <option
                v-for="(option, index) in options"
                :key="index"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>
    </div>
</template>

<script setup>
import { ref, toRefs } from "vue";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";

const props = defineProps({
    modelValue: { type: [String, Number], default: "" },
    placeholder: { type: String, default: "" },
    icon: { type: [Array, Object], default: null },
    options: {
        type: Array,
        default: () => [], // format: [{ value: '1', label: 'Satu' }]
    },
});

const emit = defineEmits(["update:modelValue"]);
</script>
