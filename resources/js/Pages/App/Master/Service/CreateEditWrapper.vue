<template>
    <div v-if="isLoading" class="min-h-[450px] space-y-3 p-1">
        <!-- Pulse Skeleton Loader for Single-Page Service Drawer -->
        <div class="animate-pulse space-y-3.5">
            <!-- Basic Section Skeleton (Foto, Nama, Kategori, Tarif Layanan) -->
            <div class="space-y-2.5">
                <div class="h-4 w-24 bg-slate-200 rounded"></div>
                <div class="h-24 w-24 bg-slate-200 rounded-xl"></div>
                <div class="h-9 bg-slate-200 rounded-lg w-full"></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div class="h-9 bg-slate-200 rounded-lg"></div>
                    <div class="h-9 bg-slate-200 rounded-lg"></div>
                </div>
            </div>

            <!-- Accordion Pengaturan Lanjutan & Progressive Disclosure Card Skeleton -->
            <div class="space-y-2.5 pt-2">
                <div class="h-14 bg-slate-200/80 rounded-xl w-full"></div>
                <div class="h-14 bg-slate-200/80 rounded-xl w-full"></div>
            </div>
        </div>

        <!-- Sticky Footer Skeleton Teleport #popUpFooter while Loading -->
        <Teleport to="#popUpFooter">
            <div class="flex items-center justify-between w-full gap-2 animate-pulse">
                <div class="h-8 w-16 bg-slate-200 rounded-lg"></div>
                <div class="h-8 w-32 bg-slate-200 rounded-lg"></div>
            </div>
        </Teleport>
    </div>

    <CreateEdit
        v-else
        :edit-mode="editMode"
        :service="fetchedService"
        :target-step-id="targetStepId"
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
    targetStepId: { type: String, default: null },
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
