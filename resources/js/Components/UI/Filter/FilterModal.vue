<template>
    <div v-show="show" class="overlay-backdrop z-50">
        <div class="overlay-modal max-w-md">
            <!-- Header -->
            <div class="overlay-header">
                <h3 class="overlay-title">{{ title }}</h3>
                <button
                    type="button"
                    class="overlay-close"
                    @click="$emit('close')"
                >
                    ✖
                </button>
            </div>

            <!-- Body -->
            <div class="p-5 space-y-4">
                <slot />
            </div>

            <!-- Footer -->
            <div class="overlay-footer">
                <button
                    type="button"
                    class="btn btn-outline-main btn-sm rounded-lg"
                    @click="$emit('reset')"
                >
                    Reset
                </button>
                <button
                    type="button"
                    class="btn btn-outline-neutral-400 btn-sm rounded-lg"
                    @click="$emit('close')"
                >
                    Batal
                </button>
                <button
                    type="button"
                    class="btn btn-highlight-main btn-sm rounded-lg"
                    @click="$emit('apply')"
                >
                    Terapkan
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    title: {
        type: String,
        default: 'Filter',
    },
});

defineEmits(['close', 'reset', 'apply']);
</script>
