<template>
    <div class="inline-flex items-center cursor-pointer select-none group">
        <input
            type="checkbox"
            :id
            class="sr-only"
            :checked="modelValue"
            @change="updateValue($event.target.checked)"
        />
        <label
            :for="id"
            class="w-8 h-5 flex items-center bg-gray-300 rounded-full p-1 transition-colors duration-300 ease-in-out group-has-checked:bg-main group/switch"
            :class="{
                'w-6! h-4!': size === 'sm',
                'w-10! h-6!': size === 'lg',
            }"
        >
            <span
                class="bg-white w-3 h-3 rounded-full shadow-md transform transition-transform duration-300 ease-in-out group-has-checked:translate-x-3"
                :class="{
                    'w-2.5! h-2.5! group-has-checked:!translate-x-1.5':
                        size === 'sm',
                    'w-4! h-4! group-has-checked:!translate-x-4': size === 'lg',
                }"
            ></span>
        </label>

        <label
            :for="id"
            class="ml-2 text-gray-700 cursor-pointer"
            :class="{
                'text-sm': size === 'sm',
                'text-lg': size === 'lg',
            }"
            >{{ labeling }}</label
        >
    </div>
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
