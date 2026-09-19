<template>
    <ActionsDropdown
        :label="label"
        :icon="icon || faDownload"
        :button-class="buttonClass"
        :align="align"
        :items="items"
        :menu-width="180"
    >
        <template v-if="$slots.default" #default="{ close }">
            <slot :close="close" />
        </template>
    </ActionsDropdown>
</template>

<script setup>
import { faDownload } from '@fortawesome/free-solid-svg-icons'
import ActionsDropdown from './ActionsDropdown.vue'

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
        default: 'btn btn-flat btn-sm h-[30px] inline-flex items-center gap-1.5 text-xs leading-4',
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
</script>
