<template>
    <div class="mb-3">
        <!-- Desktop & Tablet Stepper -->
        <div class="flex items-center justify-between">
            <template v-for="(step, index) in steps" :key="step.id || index">
                <div
                    class="flex flex-col items-center cursor-pointer group"
                    @click="handleStepClick(index)"
                >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold transition-all select-none relative"
                        :class="[
                            index < currentStepIndex
                                ? 'bg-main text-white'
                                : index === currentStepIndex
                                  ? 'bg-main text-white ring-2 ring-main/20'
                                  : 'bg-slate-100 text-slate-500 group-hover:bg-slate-200',
                            hasStepError(step, index)
                                ? 'bg-danger text-white ring-2 ring-danger/20'
                                : '',
                        ]"
                    >
                        <span v-if="index < currentStepIndex && !hasStepError(step, index)">✓</span>
                        <span v-else>{{ index + 1 }}</span>

                        <!-- Error Dot -->
                        <span
                            v-if="hasStepError(step, index)"
                            class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 bg-danger rounded-full ring-2 ring-white"
                        ></span>
                    </div>

                    <span
                        class="mt-1.5 text-xs font-medium transition-colors select-none text-center max-w-[100px] truncate"
                        :class="[
                            index === currentStepIndex
                                ? 'text-main font-semibold'
                                : hasStepError(step, index)
                                  ? 'text-danger font-medium'
                                  : 'text-slate-500 group-hover:text-slate-700',
                        ]"
                    >
                        {{ step.title }}
                    </span>
                </div>

                <!-- Connecting Line -->
                <div
                    v-if="index < steps.length - 1"
                    class="h-0.5 flex-1 bg-slate-100 mx-2 -mt-4 rounded-full overflow-hidden"
                >
                    <div
                        class="h-full bg-main transition-all duration-300"
                        :style="{
                            width: index < currentStepIndex ? '100%' : '0%',
                        }"
                    ></div>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    steps: {
        type: Array,
        required: true,
    },
    currentStepIndex: {
        type: Number,
        default: 0,
    },
    allowStepClick: {
        type: Boolean,
        default: false,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
})

const emit = defineEmits(['update:currentStepIndex', 'step-click'])

const hasStepError = (step, index) => {
    if (!props.errors || Object.keys(props.errors).length === 0) return false
    if (step.fields && Array.isArray(step.fields)) {
        return step.fields.some(field => Boolean(props.errors[field]))
    }
    return false
}

const handleStepClick = index => {
    if (props.allowStepClick || index < props.currentStepIndex) {
        emit('update:currentStepIndex', index)
        emit('step-click', index)
    }
}
</script>
