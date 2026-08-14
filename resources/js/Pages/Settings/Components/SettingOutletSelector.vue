<template>
    <div v-if="outlets && outlets.length > 0" class="flex items-center gap-2">
        <label for="setting-outlet-select" class="text-xs font-semibold text-slate-500 whitespace-nowrap hidden sm:inline">
            Outlet:
        </label>
        <select
            id="setting-outlet-select"
            class="form-select text-sm py-1.5 px-3 rounded-lg border-slate-300 bg-white shadow-xs focus:border-main focus:ring-main"
            :value="modelValue"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option v-for="outlet in outlets" :key="outlet.id" :value="outlet.id">
                {{ outlet.name }}
            </option>
        </select>
    </div>
</template>

<script setup>
defineProps({
    outlets: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: String,
        default: '',
    },
});

defineEmits(['update:modelValue']);
</script>
