<template>
    <div v-if="isLoading" class="min-h-[400px] p-6">
        <!-- Pulse Skeleton Loader -->
        <div class="animate-pulse space-y-6">
            <!-- Stepper Skeleton -->
            <div class="flex items-center justify-between mb-8">
                <div v-for="i in 3" :key="i" class="flex flex-col items-center gap-2">
                    <div class="h-8 w-8 bg-slate-200 rounded-full"></div>
                    <div class="h-3 w-16 bg-slate-200 rounded-full"></div>
                </div>
            </div>

            <!-- Content Skeleton -->
            <div class="space-y-4">
                <div class="h-6 w-1/4 bg-slate-200 rounded-lg"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="h-10 bg-slate-200 rounded-lg"></div>
                    <div class="h-10 bg-slate-200 rounded-lg"></div>
                    <div class="h-20 bg-slate-200 rounded-lg col-span-2"></div>
                </div>
            </div>
        </div>
    </div>

    <CreateEdit
        v-else
        :edit-mode="editMode"
        :product="fetchedProduct"
        :initial-step="initialStep"
        :target-step-id="targetStepId"
        :categories="loadedCategories"
        :outlets="loadedOutlets"
        :uoms="loadedUoms"
    />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import CreateEdit from './CreateEdit.vue'

const props = defineProps({
    editMode: { type: Boolean, default: false },
    product: { type: Object, default: null },
    initialStep: { type: Number, default: 0 },
    targetStepId: { type: String, default: null },
    categories: { type: Array, default: () => [] },
    outlets: { type: Array, default: () => [] },
    uoms: { type: Array, default: () => [] },
})

const loadedCategories = ref(props.categories)
const loadedOutlets = ref(props.outlets)
const loadedUoms = ref(props.uoms)
const isLoading = ref(true)
const fetchedProduct = ref(props.product)

onMounted(async () => {
    try {
        const promises = []

        // Load master form options on demand if not already provided
        if (
            loadedCategories.value.length === 0 ||
            loadedOutlets.value.length === 0 ||
            loadedUoms.value.length === 0
        ) {
            promises.push(
                axios.get(route('master.products.formOptions')).then(res => {
                    loadedCategories.value = res.data.categories || []
                    loadedOutlets.value = res.data.outlets || []
                    loadedUoms.value = res.data.uoms || []
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
