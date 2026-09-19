<template>
    <div v-if="isLoading" class="min-h-[400px] space-y-3">
        <!-- Pulse Skeleton Loader -->
        <div class="animate-pulse space-y-4">
            <!-- Stepper Skeleton -->
            <div class="flex items-center justify-between mb-4">
                <div v-for="i in 3" :key="i" class="flex flex-col items-center gap-1.5">
                    <div class="size-8 bg-slate-200 rounded-full"></div>
                    <div class="h-2.5 w-14 bg-slate-200 rounded-full"></div>
                </div>
            </div>

            <!-- Content Skeleton -->
            <div class="space-y-3">
                <div class="h-5 w-1/4 bg-slate-200 rounded-lg"></div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="h-9 bg-slate-200 rounded-lg"></div>
                    <div class="h-9 bg-slate-200 rounded-lg"></div>
                    <div class="h-16 bg-slate-200 rounded-lg col-span-2"></div>
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
