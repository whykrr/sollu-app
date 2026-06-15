<template>
    <div class="flex flex-wrap gap-1">
        <div
            v-for="(opt, idx) in options"
            :key="idx"
            class="form-check"
            :class="$attrs.class"
        >
            <input
                v-bind="$attrs"
                :id="$attrs.name + idx"
                type="radio"
                class="form-check-btn peer"
                :value="opt.value"
                :checked="modelValue === opt.value"
                @change="emit('update:modelValue', $event.target.value)"
            />
            <label
                class="btn"
                :class="[
                    {
                        'btn-outline-main': modelValue === opt.value,
                        'btn-outline-neutral-400': modelValue !== opt.value,
                    },
                    $attrs.class,
                ]"
                :for="$attrs.name + idx"
            >
                <FontAwesomeIcon
                    v-if="modelValue === opt.value"
                    :icon="faCircleCheck"
                />
                <FontAwesomeIcon v-else :icon="faCircle" />
                {{ opt.label }}
            </label>
        </div>
    </div>
</template>
<script setup>
import { faCircle, faCircleCheck } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    label: String,
    options: Array,
    modelValue: String,
});
const emit = defineEmits(['update:modelValue']);
</script>
