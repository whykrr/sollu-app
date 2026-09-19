<template>
    <div class="flex border-b border-slate-200 mb-3 gap-1 overflow-x-auto hide-scrollbar">
        <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            class="px-3 py-2 text-xs font-medium border-b-2 transition-all -mb-px flex items-center gap-1.5 whitespace-nowrap select-none rounded-t-lg"
            :class="[
                activeTabId === tab.id
                    ? 'border-main text-main font-semibold bg-main/5'
                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50',
                hasTabError(tab) ? '!border-danger !text-danger' : '',
            ]"
            @click="selectTab(tab.id)"
        >
            <FontAwesomeIcon
                v-if="tab.icon"
                :icon="tab.icon"
                class="text-xs transition-colors"
                :class="
                    hasTabError(tab)
                        ? 'text-danger'
                        : activeTabId === tab.id
                          ? 'text-main'
                          : 'text-slate-400'
                "
            />
            <span>{{ tab.title }}</span>

            <!-- Error Indicator Dot -->
            <span
                v-if="hasTabError(tab)"
                class="w-1.5 h-1.5 rounded-full bg-danger inline-block"
                title="Terdapat error validasi pada bagian ini"
            ></span>
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
    modelValue: {
        type: [String, Number],
        required: true,
    },
    tabs: {
        type: Array,
        required: true,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
})

const emit = defineEmits(['update:modelValue', 'change'])

const activeTabId = computed(() => props.modelValue)

const hasTabError = tab => {
    if (!props.errors || Object.keys(props.errors).length === 0) return false
    if (tab.fields && Array.isArray(tab.fields)) {
        return tab.fields.some(field => Boolean(props.errors[field]))
    }
    return false
}

const selectTab = tabId => {
    emit('update:modelValue', tabId)
    emit('change', tabId)
}
</script>
