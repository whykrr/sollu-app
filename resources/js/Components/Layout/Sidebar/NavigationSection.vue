<template>
    <div class="nav-section" role="heading" aria-level="3">
        <div v-if="separator" class="pt-2 pb-1">
            <div
                class="w-full border-t border-slate-200/80 pt-2 px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 select-none"
            >
                {{ label }}
            </div>
        </div>
        <div
            v-else
            class="px-3 pt-3 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400 select-none"
        >
            {{ label }}
        </div>
    </div>
</template>

<script setup>
defineProps({
    label: {
        type: String,
        default: '',
    },
    separator: {
        type: Boolean,
        default: false,
    },
})
</script>
