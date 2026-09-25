<template>
    <div v-if="isLoading" class="min-h-[350px] space-y-3">
        <!-- Pulse Skeleton Loader -->
        <div class="animate-pulse space-y-4">
            <div class="space-y-3">
                <div class="h-4 w-1/4 bg-slate-200 rounded-lg"></div>
                <div class="h-28 bg-slate-200 rounded-xl"></div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="h-9 bg-slate-200 rounded-lg col-span-2"></div>
                    <div class="h-9 bg-slate-200 rounded-lg"></div>
                    <div class="h-9 bg-slate-200 rounded-lg"></div>
                </div>
            </div>
        </div>
    </div>

    <CreateEdit
        v-else
        :edit-mode="editMode"
        :service="fetchedService"
        :categories="loadedCategories"
        :outlets="loadedOutlets"
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
    service: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    outlets: { type: Array, default: () => [] },
})

const loadedCategories = ref(props.categories)
const loadedOutlets = ref(props.outlets)
const isLoading = ref(true)
const fetchedService = ref(props.service)

onMounted(async () => {
    try {
        const promises = []

        // Load master form options on demand if not already provided
        if (loadedCategories.value.length === 0 || loadedOutlets.value.length === 0) {
            promises.push(
                axios.get(route('master.services.formOptions')).then(res => {
                    loadedCategories.value = res.data.categories || []
                    loadedOutlets.value = res.data.outlets || []
                })
            )
        }

        // Fetch detailed service with all relationships when editing
        if (props.editMode && props.service?.id) {
            promises.push(
                axios.get(route('master.services.show', props.service.id)).then(res => {
                    fetchedService.value = res.data.data
                })
            )
        }

        if (promises.length > 0) {
            await Promise.all(promises)
        }
    } catch (error) {
        console.error('Failed to load service form data:', error)
    } finally {
        isLoading.value = false
    }
})
</script>
