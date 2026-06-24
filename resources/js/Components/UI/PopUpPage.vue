<script setup>
const props = defineProps({
    title: String,
    subTitle: {
        type: String,
        default: null,
    },
    size: {
        type: String,
        default: 'md',
    },
});

const emit = defineEmits(['close']);
const closePage = () => {
    emit('close');
};
</script>
<template>
    <div class="modal !justify-end pt-4">
        <div
            class="modal-dialog side h-[100%]"
            :class="{
                'max-w-md': size == 'sm',
                'max-w-lg': size == 'md',
                'max-w-2xl': size == 'lg',
                'max-w-4xl': size == 'xl',
            }"
        >
            <div class="modal-content h-full flex flex-col">
                <!-- Modal Header -->
                <div class="modal-header border-b-0 shrink-0 sticky top-0 p-4">
                    <div class="font-bold">
                        <span class="text-xl"
                            >{{ title }}
                            <span v-if="subTitle" class="text-neutral-400">{{
                                subTitle
                            }}</span></span
                        >
                    </div>
                    <button
                        id="closeModalBtn"
                        class="text-gray-500 hover:text-gray-700 focus:outline-none"
                        @click="closePage"
                    >
                        ✖
                    </button>
                </div>
                <!-- Modal Body -->
                <div
                    class="modal-body pt-0 flex-1 overflow-y-auto floating-scroll [mask-image:linear-gradient(to_bottom,black_95%,transparent)]"
                >
                    <slot />
                </div>
                <!-- Modal Footer -->
                <div
                    v-if="$slots.footer"
                    class="modal-footer bottom-0 z-20 bg-white p-4 border-t-0"
                >
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </div>
</template>
