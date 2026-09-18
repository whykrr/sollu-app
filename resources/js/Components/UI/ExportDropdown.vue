<template>
    <div ref="dropdownRef" class="relative inline-block text-left">
        <button
            type="button"
            :class="[
                buttonClass ||
                    'btn btn-flat btn-sm h-[30px] inline-flex items-center gap-1.5 text-xs leading-4',
                'select-none cursor-pointer',
            ]"
            @click="toggle"
        >
            <FontAwesomeIcon :icon="icon || faDownload" class="text-xs" />
            <span class="text-xs leading-4">{{ label }}</span>
            <FontAwesomeIcon
                :icon="faChevronDown"
                class="text-[10px] text-slate-400 transition-transform duration-200"
                :class="{ 'rotate-180': isOpen }"
            />
        </button>

        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                :class="[
                    align === 'left' ? 'left-0' : 'right-0',
                    'absolute top-full mt-1.5 z-50 min-w-[170px] rounded-xl bg-white p-1 shadow-lg ring-1 ring-black/5 focus:outline-none border border-gray-200',
                ]"
            >
                <slot :close="close">
                    <button
                        v-for="(item, index) in items"
                        :key="index"
                        type="button"
                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-neutral-700 transition hover:bg-gray-50 hover:text-neutral-900"
                        :class="item.class"
                        @click="handleItemClick(item)"
                    >
                        <FontAwesomeIcon v-if="item.icon" :icon="item.icon" class="text-xs" />
                        <span>{{ item.label }}</span>
                    </button>
                </slot>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faDownload, faChevronDown } from '@fortawesome/free-solid-svg-icons'
import { useDropdown } from '@/Composable/useDropdown'

defineProps({
    label: {
        type: String,
        default: 'Ekspor Data',
    },
    icon: {
        type: Object,
        default: null,
    },
    buttonClass: {
        type: String,
        default: 'btn btn-flat btn-sm',
    },
    align: {
        type: String,
        default: 'right',
        validator: value => ['left', 'right'].includes(value),
    },
    items: {
        type: Array,
        default: () => [],
    },
})

const { isOpen, dropdownRef, toggle, close } = useDropdown()

const handleItemClick = item => {
    if (typeof item.action === 'function') {
        item.action()
    }
    close()
}
</script>
