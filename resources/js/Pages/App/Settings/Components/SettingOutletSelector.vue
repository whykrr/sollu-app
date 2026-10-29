<template>
    <div
        v-if="outlets && outlets.length > 1 && !isLockedToOutlet"
        v-feature="$enums.FeatureEnum.MULTI_OUTLET"
        class="flex items-center gap-2"
    >
        <label
            for="setting-outlet-select"
            class="text-xs font-semibold text-slate-500 whitespace-nowrap hidden sm:inline"
        >
            Outlet:
        </label>
        <select
            id="setting-outlet-select"
            class="form sm text-xs leading-4 rounded-lg border-slate-200 bg-white focus:border-main focus:ring-main focus:ring-inset h-[30px]"
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
import { computed } from 'vue'
import { useAuth } from '@/Composable/useAuth'

const { auth } = useAuth()

// Sembunyikan selector hanya jika karyawan terikat ke outlet tertentu
const isLockedToOutlet = computed(() => Boolean(auth.value?.selected_outlet))

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
