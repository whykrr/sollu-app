<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Personalisasi Fitur"
                description="Aktifkan atau nonaktifkan fitur sesuai dengan kebutuhan usaha Anda. Fitur yang ditandai dengan ikon gembok tidak tersedia pada paket langganan Anda saat ini."
            />
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 pb-8">
            <div
                v-for="(features, groupName) in featureGroups"
                :key="groupName"
                class="bg-white rounded-xl border border-slate-200 p-5"
            >
                <h3
                    class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4"
                >
                    {{ groupName }}
                </h3>

                <div class="space-y-3">
                    <div
                        v-for="feature in features"
                        :key="feature.value"
                        v-feature.mask="feature.value"
                        class="rounded-lg border transition-colors overflow-hidden relative"
                        :class="[
                            isAvailable(feature.value)
                                ? 'border-slate-100 bg-slate-50/50 hover:bg-slate-50'
                                : 'border-slate-200 bg-slate-100/50',
                        ]"
                    >
                        <div class="flex items-start justify-between gap-4 p-3.5">
                            <div class="flex-1">
                                <h4
                                    class="text-sm font-medium"
                                    :class="
                                        isAvailable(feature.value)
                                            ? 'text-slate-800'
                                            : 'text-slate-500'
                                    "
                                >
                                    {{ feature.label }}
                                </h4>
                                <p
                                    class="text-xs mt-1 leading-relaxed"
                                    :class="
                                        isAvailable(feature.value)
                                            ? 'text-slate-500'
                                            : 'text-slate-400'
                                    "
                                >
                                    {{ feature.description }}
                                </p>
                            </div>

                            <div class="mt-0.5 shrink-0">
                                <Switch
                                    :id="'feature-' + feature.value"
                                    :model-value="form.features.includes(feature.value)"
                                    :disabled="
                                        !isAvailable(feature.value) ||
                                        form.processing ||
                                        isResetting
                                    "
                                    @update:model-value="toggleFeature(feature.value)"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Bottom Action Bar -->
        <div
            class="flex items-center justify-between sticky bottom-4 z-20 bg-white/90 backdrop-blur-xs p-3 rounded-xl border border-slate-200 mx-0 lg:mx-0"
        >
            <div>
                <button
                    type="button"
                    class="btn flex items-center gap-2"
                    :class="
                        isPersonalized
                            ? 'btn-outline-danger'
                            : 'btn-flat text-slate-400 border-slate-200 cursor-not-allowed'
                    "
                    :disabled="!isPersonalized || form.processing || isResetting"
                    @click="confirmReset"
                >
                    <FontAwesomeIcon :icon="faRotateLeft" />
                    <span>{{ isResetting ? 'Mereset...' : 'Reset ke Standar' }}</span>
                </button>
            </div>

            <button
                class="btn btn-main flex items-center gap-2"
                :disabled="form.processing || isResetting"
                @click="save"
            >
                <FontAwesomeIcon :icon="faSave" />
                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}</span>
            </button>
        </div>
    </MainPage>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faRotateLeft, faSave } from '@fortawesome/free-solid-svg-icons'
import { useModalStore } from '@/store/notification'

import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Switch from '@/Components/Form/Switch.vue'

const props = defineProps({
    availableFeatures: {
        type: Array,
        default: () => [],
    },
    activeFeatures: {
        type: Array,
        default: () => [],
    },
    featureGroups: {
        type: Object,
        default: () => ({}),
    },
    isPersonalized: {
        type: Boolean,
        default: false,
    },
})

const modalStore = useModalStore()
const isResetting = ref(false)

const form = useForm({
    features: [...props.activeFeatures],
})

watch(
    () => props.activeFeatures,
    newFeatures => {
        form.features = [...newFeatures]
    },
    { deep: true }
)

const isAvailable = featureValue => {
    return props.availableFeatures.includes(featureValue)
}

const toggleFeature = featureValue => {
    if (!isAvailable(featureValue)) return

    const index = form.features.indexOf(featureValue)
    if (index === -1) {
        form.features.push(featureValue)
    } else {
        form.features.splice(index, 1)
    }
}

const save = () => {
    form.put(route('settings.business.features.save'), {
        preserveState: true,
        preserveScroll: true,
    })
}

const confirmReset = () => {
    modalStore.confirm({
        title: 'Reset Pengaturan Fitur?',
        message:
            'Pengaturan fitur akan dikembalikan ke standar bawaan paket langganan dan jenis usaha Anda. Personalisasi fitur saat ini akan dihapus.',
        type: 'warning',
        confirmText: 'Ya, Reset ke Standar',
        cancelText: 'Batal',
        onConfirm: () => {
            isResetting.value = true
            router.delete(route('settings.business.features.reset'), {
                preserveScroll: true,
                onFinish: () => {
                    isResetting.value = false
                },
            })
        },
    })
}
</script>
