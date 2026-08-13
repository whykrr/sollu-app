<template>
    <div class="space-y-1.5 w-full">
        <div v-if="label || (multiple && showSelectAll)" class="flex items-center justify-between">
            <label v-if="label" class="label">{{ label }}</label>
            <button
                v-if="multiple && showSelectAll && options?.length"
                type="button"
                class="text-xs font-semibold text-primary-600 hover:text-primary-700 hover:bg-primary-50 active:bg-primary-100 transition-all rounded px-2 py-0.5 select-none ml-auto"
                @click="toggleSelectAll"
            >
                {{ isAllSelected ? deselectAllLabel : selectAllLabel }}
            </button>
        </div>
        <div class="flex flex-wrap gap-1">
            <div
                v-for="(opt, idx) in options"
                :key="idx"
                class="form-check"
                :class="$attrs.class"
            >
                <input
                    :id="inputName + idx"
                    :name="name || inputName"
                    :type="multiple ? 'checkbox' : 'radio'"
                    class="form-check-btn peer"
                    :value="opt.value"
                    :checked="isSelected(opt.value)"
                    :disabled="disabled"
                    @change="handleSelect(opt.value)"
                />
                <label
                    class="btn"
                    :class="[
                        {
                            'btn-outline-main': isSelected(opt.value),
                            'btn-outline-neutral-400': !isSelected(opt.value),
                            'opacity-50 cursor-not-allowed': disabled,
                        },
                        $attrs.class,
                    ]"
                    :for="inputName + idx"
                >
                    <FontAwesomeIcon
                        v-if="isSelected(opt.value)"
                        :icon="faCircleCheck"
                    />
                    <FontAwesomeIcon v-else :icon="faCircle" />
                    {{ opt.label }}
                </label>
            </div>
        </div>
        <div v-if="feedback" class="text-danger text-xs select-none">
            {{ feedback }}
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { faCircle, faCircleCheck } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineOptions({
    name: 'SelectionGroupField',
});

const props = defineProps({
    label: String,
    name: String,
    options: {
        type: Array,
        default: () => [],
    },
    modelValue: [Array, String, Number, Boolean],
    multiple: {
        type: Boolean,
        default: false,
    },
    showSelectAll: {
        type: Boolean,
        default: false,
    },
    selectAllLabel: {
        type: String,
        default: 'Pilih Semua',
    },
    deselectAllLabel: {
        type: String,
        default: 'Batalkan Semua',
    },
    feedback: String,
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'update:allSelected', 'all-selected']);

const inputName = computed(() => props.name || 'sel_');

function isSelected(val) {
    if (props.multiple) {
        if (!props.modelValue || !Array.isArray(props.modelValue)) return false;
        return props.modelValue.some((v) => v == val || String(v) === String(val));
    }
    if (props.modelValue === null || props.modelValue === undefined) return false;
    return props.modelValue == val || String(props.modelValue) === String(val);
}

const isAllSelected = computed(() => {
    return (
        props.multiple &&
        props.options.length > 0 &&
        props.options.every((opt) => isSelected(opt.value))
    );
});

function toggleSelectAll() {
    if (!props.multiple) return;

    if (isAllSelected.value) {
        emit('update:modelValue', []);
        emit('update:allSelected', false);
        emit('all-selected', { isAllSelected: false, values: [] });
    } else {
        const allVals = props.options.map((opt) => opt.value);
        emit('update:modelValue', allVals);
        emit('update:allSelected', true);
        emit('all-selected', { isAllSelected: true, values: allVals });
    }
}

function handleSelect(val) {
    if (!props.multiple) {
        emit('update:modelValue', val);
        return;
    }

    const current = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
    const index = current.findIndex((v) => v == val || String(v) === String(val));

    if (index === -1) {
        current.push(val);
    } else {
        current.splice(index, 1);
    }

    const isAll =
        props.options.length > 0 &&
        props.options.every((opt) =>
            current.some((v) => v == opt.value || String(v) === String(opt.value)),
        );

    emit('update:modelValue', current);
    emit('update:allSelected', isAll);
    emit('all-selected', { isAllSelected: isAll, values: current });
}
</script>
