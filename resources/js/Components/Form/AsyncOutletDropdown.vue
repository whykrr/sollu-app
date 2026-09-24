<template>
    <div v-if="!shouldHide" class="relative">
        <SearchableDropdownField
            :id="$attrs.id || 'outlet_id'"
            v-model="internalValue"
            :label="label"
            :placeholder="placeholder"
            search-placeholder="Cari nama outlet..."
            :options="formattedOutlets"
            :error="error"
            :feedback="feedback"
            :disabled="disabled || isLoading"
            :searchable="true"
            :size="size"
        />
    </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { useAuth } from '@/Composable/useAuth.js'
import SearchableDropdownField from '@/Components/Form/SearchableDropdownField.vue'

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Pilih Outlet',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    feedback: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
    excludeFrozen: {
        type: Boolean,
        default: false,
    },
    showAlways: {
        type: Boolean,
        default: false,
    },
    size: {
        type: String,
        default: 'base',
        validator: v => ['sm', 'base', 'adaptive', 'lg'].includes(v),
    },
})

const emit = defineEmits(['update:modelValue', 'change', 'loaded'])

const internalValue = ref(props.modelValue)

const { outlets: sharedOutlets, selectedOutlet } = useAuth()
const isLoading = ref(false)

const outlets = computed(() => {
    if (props.excludeFrozen) {
        return sharedOutlets.value.filter(o => !o.is_stock_frozen)
    }
    return sharedOutlets.value
})

const formattedOutlets = computed(() => {
    return outlets.value.map(o => ({
        value: o.id,
        label: o.name,
        description: o.address || (o.code ? `Kode: ${o.code}` : null),
    }))
})

const shouldHide = computed(() => {
    if (props.showAlways) return false
    return Boolean(selectedOutlet.value || outlets.value.length <= 1)
})

const resolveDefaultOutlet = () => {
    if (selectedOutlet.value) {
        if (!internalValue.value || internalValue.value !== selectedOutlet.value.id) {
            internalValue.value = selectedOutlet.value.id
            emit('update:modelValue', selectedOutlet.value.id)
            emit('change', selectedOutlet.value)
        }
    } else if (outlets.value.length === 1) {
        const onlyOutlet = outlets.value[0]
        if (!internalValue.value || internalValue.value !== onlyOutlet.id) {
            internalValue.value = onlyOutlet.id
            emit('update:modelValue', onlyOutlet.id)
            emit('change', onlyOutlet)
        }
    }
}

watch(
    () => props.modelValue,
    newVal => {
        internalValue.value = newVal
        if (!newVal) {
            resolveDefaultOutlet()
        }
    }
)

watch(internalValue, newVal => {
    emit('update:modelValue', newVal)
    const selected = outlets.value.find(o => o.id == newVal)
    emit('change', selected || null)
})

watch(
    [outlets, selectedOutlet],
    () => {
        resolveDefaultOutlet()
        if (outlets.value && outlets.value.length > 0) {
            emit('loaded', outlets.value)
        }
    },
    { immediate: true, deep: true }
)

onMounted(() => {
    resolveDefaultOutlet()
})
</script>
