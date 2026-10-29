<template>
    <div
        :id="id"
        v-feature="feature"
        class="border rounded-xl transition-colors duration-150 relative"
        :class="[
            hasError
                ? 'border-danger-300 bg-danger-50/20'
                : isOpen
                  ? 'border-slate-300 bg-white'
                  : 'border-slate-200 bg-white hover:border-slate-300',
        ]"
    >
        <!-- Header / Toggle Row -->
        <div
            class="flex items-center justify-between p-3 select-none rounded-xl cursor-pointer hover:bg-slate-50/60"
            @click="handleHeaderClick"
        >
            <!-- Left: Icon & Title & Description & Badges -->
            <div class="flex items-center gap-2.5 min-w-0 pr-2">
                <div
                    v-if="icon"
                    class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-colors"
                    :class="switchValue ? 'bg-main/10 text-main' : 'bg-slate-100 text-slate-400'"
                >
                    <FontAwesomeIcon :icon="icon" class="text-xs" />
                </div>

                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-700">
                            {{ title }}
                        </span>

                        <!-- Badge -->
                        <span
                            v-if="badge !== null && badge !== undefined && badge !== ''"
                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium"
                            :class="
                                hasError
                                    ? 'bg-danger-100 text-danger-700'
                                    : switchValue
                                      ? 'bg-main/10 text-main'
                                      : 'bg-slate-100 text-slate-600'
                            "
                        >
                            {{ badge }}
                        </span>

                        <!-- Error Indicator Dot -->
                        <span
                            v-if="hasError"
                            class="inline-block w-2 h-2 rounded-full bg-danger-500 animate-pulse"
                            title="Terdapat input yang perlu diperbaiki"
                        ></span>
                    </div>

                    <p v-if="description" class="text-xs text-slate-500 mt-0.5 truncate">
                        {{ description }}
                    </p>
                </div>
            </div>

            <!-- Right: Switch and/or Chevron -->
            <div class="flex items-center gap-2 shrink-0">
                <div v-if="hasSwitch" @click.stop>
                    <Switch
                        :model-value="switchValue"
                        size="sm"
                        @update:model-value="handleSwitchChange"
                    />
                </div>

                <div
                    class="w-6 h-6 flex items-center justify-center rounded-lg text-slate-400 transition-transform duration-200"
                    :class="{ 'rotate-180 text-slate-600': isOpen }"
                >
                    <FontAwesomeIcon :icon="faChevronDown" class="text-xs" />
                </div>
            </div>
        </div>

        <!-- Collapsible Body -->
        <div v-show="isOpen" class="px-3.5 pb-3.5 pt-2 border-t border-slate-100 space-y-3">
            <slot />
        </div>
    </div>
</template>

<script setup>
import { computed, watch } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faChevronDown } from '@fortawesome/free-solid-svg-icons'
import Switch from '@/Components/Form/Switch.vue'

const props = defineProps({
    id: { type: String, default: null },
    title: { type: String, required: true },
    description: { type: String, default: '' },
    icon: { type: Object, default: null },
    feature: { type: String, default: null },
    hasSwitch: { type: Boolean, default: true },
    switchValue: { type: Boolean, default: false },
    isOpen: { type: Boolean, default: false },
    badge: { type: [String, Number], default: null },
    error: { type: [Boolean, String], default: false },
})

const emit = defineEmits(['update:switchValue', 'update:isOpen', 'switch-change', 'toggle'])

const hasError = computed(() => Boolean(props.error))

watch(
    () => props.error,
    newErr => {
        if (newErr && !props.isOpen) {
            emit('update:isOpen', true)
        }
    }
)

const handleHeaderClick = () => {
    const nextOpen = !props.isOpen
    emit('update:isOpen', nextOpen)
    emit('toggle', nextOpen)
}

const handleSwitchChange = val => {
    emit('update:switchValue', val)
    emit('switch-change', val)
    if (val && !props.isOpen) {
        emit('update:isOpen', true)
    }
}
</script>
