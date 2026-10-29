<template>
    <div v-if="isLoading" class="min-h-[450px] space-y-3 p-1">
        <!-- Pulse Skeleton Loader for Single-Page Drawer -->
        <div class="animate-pulse space-y-3.5">
            <!-- Basic Section Skeleton -->
            <div class="space-y-2.5">
                <div class="h-4 w-24 bg-slate-200 rounded"></div>
                <div class="h-24 w-24 bg-slate-200 rounded-xl"></div>
                <div class="h-9 bg-slate-200 rounded-lg w-full"></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div class="h-9 bg-slate-200 rounded-lg"></div>
                    <div class="h-9 bg-slate-200 rounded-lg"></div>
                </div>
            </div>

            <!-- Progressive Disclosure Cards Skeleton -->
            <div class="space-y-2.5 pt-2">
                <div v-for="i in 4" :key="i" class="h-14 bg-slate-200/80 rounded-xl w-full"></div>
            </div>
        </div>
    </div>

    <CreateEdit
        v-else
        :edit-mode="editMode"
        :product="fetchedProduct"
        :target-step-id="targetStepId"
        :categories="loadedCategories"
        :outlets="loadedOutlets"
        :uoms="loadedUoms"
        :modifier-groups="loadedModifierGroups"
        :raw-materials="loadedRawMaterials"
        @close="emit('close', $event)"
    />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import CreateEdit from './CreateEdit.vue'

const emit = defineEmits(['close'])

const props = defineProps({
    editMode: { type: Boolean, default: false },
    product: { type: Object, default: null },
    targetStepId: { type: String, default: null },
    categories: { type: Array, default: () => [] },
    outlets: { type: Array, default: () => [] },
    uoms: { type: Array, default: () => [] },
    modifierGroups: { type: Array, default: () => [] },
    rawMaterials: { type: Array, default: () => [] },
})

const loadedCategories = ref(props.categories)
const loadedOutlets = ref(props.outlets)
const loadedUoms = ref(props.uoms)
const loadedModifierGroups = ref(props.modifierGroups)
const loadedRawMaterials = ref(props.rawMaterials)
const isLoading = ref(true)
const fetchedProduct = ref(props.product)

onMounted(async () => {
    try {
        const promises = []

        // Load master form options on demand if not already provided
        if (
            loadedCategories.value.length === 0 ||
            loadedOutlets.value.length === 0 ||
            loadedUoms.value.length === 0 ||
            loadedModifierGroups.value.length === 0 ||
            loadedRawMaterials.value.length === 0
        ) {
            promises.push(
                axios.get(route('master.products.formOptions')).then(res => {
                    loadedCategories.value = res.data.categories || []
                    loadedOutlets.value = res.data.outlets || []
                    loadedUoms.value = res.data.uoms || []
                    loadedModifierGroups.value = res.data.modifierGroups || []
                    loadedRawMaterials.value = res.data.rawMaterials || []
                })
            )
        }

        // Fetch detailed product with all relationships when editing
        if (props.editMode && props.product?.id) {
            promises.push(
                axios.get(route('master.products.show', props.product.id)).then(res => {
                    fetchedProduct.value = res.data.data
                })
            )
        }

        if (promises.length > 0) {
            await Promise.all(promises)
        }
    } catch (error) {
        console.error('Failed to load product form data:', error)
    } finally {
        isLoading.value = false
    }
})
</script>
