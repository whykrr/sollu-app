<template>
    <label class="inline-flex items-center cursor-pointer">
        <input
            type="checkbox"
            :id
            class="sr-only peer"
            :checked="modelValue"
            @change="updateValue($event.target.checked)"
        />
        <span
            class="w-8 h-5 flex items-center bg-gray-300 rounded p-1 transition-colors duration-300 ease-in-out peer-checked:bg-main group"
            :class="{
                '!w-6 !h-4': size === 'sm',
                '!w-10 !h-6': size === 'lg',
            }"
        >
            <span
                class="bg-white w-3 h-3 rounded shadow-md transform transition-transform duration-300 ease-in-out peer-checked:group-[]:translate-x-3"
                :class="{
                    '!w-2.5 !h-2.5 peer-checked:group-[]:!translate-x-1.5':
                        size === 'sm',
                    '!w-4 !h-4 peer-checked:group-[]:!translate-x-4':
                        size === 'lg',
                }"
            ></span>
        </span>

        <label
            :for="id"
            class="ml-2 text-gray-700 select-none"
            :class="{
                'text-sm': size === 'sm',
                'text-lg': size === 'lg',
            }"
            >{{ labeling }}</label
        >
    </label>
</template>
<script setup>
defineProps({
    id: String,
    labeling: String,
    size: String,
    modelValue: { type: Boolean, required: true },
});

const emit = defineEmits(["update:modelValue"]);

const updateValue = (value) => {
    // Emit the `update:modelValue` event for two-way binding
    emit("update:modelValue", value);
};
</script>
