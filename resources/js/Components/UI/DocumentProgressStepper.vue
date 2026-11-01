<template>
    <div class="w-full">
        <!-- Voided Banner -->
        <div
            v-if="isVoided"
            class="rounded-xl border border-rose-200 bg-rose-50/70 p-3 text-rose-900"
            role="alert"
        >
            <div class="flex items-start gap-2.5">
                <div
                    class="w-7 h-7 rounded-lg bg-rose-100 flex items-center justify-center text-rose-600 shrink-0 mt-0.5"
                >
                    <FontAwesomeIcon :icon="faBan" class="text-sm" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-800">
                            Dokumen Dibatalkan (Void)
                        </span>
                        <span v-if="voidInfo?.voided_at" class="text-[11px] text-rose-600">
                            • {{ formatDate(voidInfo.voided_at) }}
                        </span>
                    </div>
                    <p
                        v-if="voidInfo?.voided_by || voidInfo?.void_reason"
                        class="text-xs text-rose-700 mt-1"
                    >
                        <span v-if="voidInfo?.voided_by" class="font-medium">
                            Dibatalkan oleh {{ voidUserName }}.
                        </span>
                        <span v-if="voidInfo?.void_reason" class="block mt-0.5 italic">
                            "{{ voidInfo.void_reason }}"
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Rejected Banner -->
        <div
            v-else-if="isRejected"
            class="rounded-xl border border-amber-200 bg-amber-50/70 p-3 text-amber-900"
            role="alert"
        >
            <div class="flex items-start gap-2.5">
                <div
                    class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600 shrink-0 mt-0.5"
                >
                    <FontAwesomeIcon :icon="faTimesCircle" class="text-sm" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-800">
                            Dokumen Ditolak
                        </span>
                        <span v-if="rejectInfo?.rejected_at" class="text-[11px] text-amber-600">
                            • {{ formatDate(rejectInfo.rejected_at) }}
                        </span>
                    </div>
                    <p
                        v-if="rejectInfo?.rejected_by || rejectInfo?.notes || rejectInfo?.reason"
                        class="text-xs text-amber-700 mt-1"
                    >
                        <span v-if="rejectInfo?.rejected_by" class="font-medium">
                            Ditolak oleh {{ rejectUserName }}.
                        </span>
                        <span
                            v-if="rejectInfo?.notes || rejectInfo?.reason"
                            class="block mt-0.5 italic"
                        >
                            "{{ rejectInfo?.notes || rejectInfo?.reason }}"
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Standard Document Flow Stepper -->
        <div v-else class="rounded-xl border border-slate-200/90 bg-white p-3">
            <div class="flex items-center justify-between relative">
                <template v-for="(step, index) in normalizedSteps" :key="step.key">
                    <!-- Step item -->
                    <div class="flex flex-col items-center relative z-10 min-w-0 flex-1">
                        <div
                            class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold transition-all duration-200 shadow-xs"
                            :class="[
                                isStepCompleted(index)
                                    ? 'bg-emerald-600 text-white'
                                    : isStepCurrent(index)
                                      ? 'bg-main text-white ring-4 ring-main/20'
                                      : 'bg-slate-100 text-slate-400 border border-slate-200',
                            ]"
                        >
                            <FontAwesomeIcon
                                v-if="isStepCompleted(index)"
                                :icon="faCheck"
                                class="text-[11px]"
                            />
                            <span v-else>{{ index + 1 }}</span>
                        </div>
                        <span
                            class="text-[11px] mt-1.5 text-center truncate max-w-full font-medium transition-colors"
                            :class="[
                                isStepCurrent(index)
                                    ? 'text-neutral-900 font-bold'
                                    : isStepCompleted(index)
                                      ? 'text-emerald-700'
                                      : 'text-slate-400',
                            ]"
                        >
                            {{ step.label }}
                        </span>
                    </div>

                    <!-- Connecting line -->
                    <div
                        v-if="index < normalizedSteps.length - 1"
                        class="flex-1 h-0.5 mx-2 -mt-4 transition-colors duration-200"
                        :class="[isStepCompleted(index) ? 'bg-emerald-500' : 'bg-slate-200']"
                    />
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faBan, faTimesCircle, faCheck } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    steps: {
        type: Array,
        default: () => [
            { key: 'draft', label: 'Draf' },
            { key: 'pending', label: 'Menunggu' },
            { key: 'approved', label: 'Selesai' },
        ],
    },
    currentStep: {
        type: String,
        default: 'draft',
    },
    isVoided: {
        type: Boolean,
        default: false,
    },
    isRejected: {
        type: Boolean,
        default: false,
    },
    voidInfo: {
        type: Object,
        default: null,
    },
    rejectInfo: {
        type: Object,
        default: null,
    },
})

const normalizedSteps = computed(() => {
    return props.steps.map(step => {
        if (typeof step === 'string') {
            return { key: step, label: step }
        }
        return step
    })
})

const currentStepIndex = computed(() => {
    const idx = normalizedSteps.value.findIndex(s => s.key === props.currentStep)
    return idx >= 0 ? idx : 0
})

const isStepCompleted = index => index < currentStepIndex.value
const isStepCurrent = index => index === currentStepIndex.value

const voidUserName = computed(() => {
    const by = props.voidInfo?.voidedBy ?? props.voidInfo?.voided_by
    if (typeof by === 'object' && by !== null) return by.name || 'Pengguna'
    if (typeof by === 'string') {
        const isUuid = /^[0-9a-fA-F-]{36}$/.test(by)
        return isUuid ? 'Pengguna' : by
    }
    return 'Pengguna'
})

const rejectUserName = computed(() => {
    const by = props.rejectInfo?.rejectedBy ?? props.rejectInfo?.rejected_by
    if (typeof by === 'object' && by !== null) return by.name || 'Supervisor'
    if (typeof by === 'string') {
        const isUuid = /^[0-9a-fA-F-]{36}$/.test(by)
        return isUuid ? 'Supervisor' : by
    }
    return 'Supervisor'
})

const formatDate = dateStr => {
    if (!dateStr) return ''
    try {
        return new Date(dateStr).toLocaleString('id-ID', {
            dateStyle: 'medium',
            timeStyle: 'short',
        })
    } catch {
        return dateStr
    }
}
</script>
