<template>
    <div v-if="outlets && outlets.length > 1 && !selectedOutlet" class="flex items-center gap-2">
        <label
            for="setting-outlet-select"
            class="text-xs font-semibold text-slate-500 whitespace-nowrap hidden sm:inline"
        >
            Outlet:
        </label>
        <select
            id="setting-outlet-select"
            class="form sm text-xs leading-4 rounded-lg border-slate-200 bg-white focus:border-main focus:ring-main h-[30px]"
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
import { useAuth } from '@/Composable/useAuth'

const { selectedOutlet } = useAuth()

defineProps({
    outlets: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: String,
        default: '',
    },
})

defineEmits(['update:modelValue'])
</script>
