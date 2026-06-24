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
                type="checkbox"
                class="form-check-btn peer"
                :value="opt.value"
                :checked="modelValue.includes(opt.value)"
                @change="toggleValue(opt.value)"
            />
            <label
                class="btn"
                :class="[
                    {
                        'btn-outline-main': modelValue.includes(opt.value),
                        'btn-outline-neutral-400': !modelValue.includes(
                            opt.value,
                        ),
                    },
                    $attrs.class,
                ]"
                :for="$attrs.name + idx"
            >
                <FontAwesomeIcon
                    v-if="modelValue.includes(opt.value)"
                    :icon="faCircleCheck"
                />
                <FontAwesomeIcon v-else :icon="faCircle" />
                {{ opt.label }}</label
            >
        </div>
    </div>
</template>
<script setup>
import {
    faCheck,
    faCircle,
    faCircleCheck,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    label: String,
    options: Array,
    modelValue: Array,
    feedback: String,
});
const emit = defineEmits(['update:modelValue']);

function toggleValue(value) {
    const newValue = [...props.modelValue];
    const index = newValue.indexOf(value);

    if (index === -1) {
        newValue.push(value);
    } else {
        newValue.splice(index, 1);
    }

    emit('update:modelValue', newValue);
}
</script>
