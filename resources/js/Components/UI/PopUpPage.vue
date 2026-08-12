<template>
  <aside
    class="modal !justify-end pt-4"
    :class="{ show: show }"
  >
    <div
      class="modal-dialog side h-[100%]"
      :class="[sizeClasses[size] || size]"
    >
      <div class="modal-content h-full flex flex-col">
        <!-- Modal Header -->
        <div
          class="modal-header border-b-0 shrink-0 sticky top-0 p-5 pb-2 bg-white z-10"
        >
          <div class="font-bold">
            <span class="text-xl">
              {{ title }}
              <span
                v-if="subTitle"
                class="text-neutral-400 font-normal text-base ml-1"
              >
                {{ subTitle }}
              </span>
            </span>
          </div>
          <button
            id="closeModalBtn"
            type="button"
            class="text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg p-1.5 transition cursor-pointer flex items-center justify-center"
            aria-label="Tutup"
            @click="closePage"
          >
            ✖
          </button>
        </div>

        <!-- Modal Body -->
        <div
          class="modal-body pt-0 flex-1 overflow-y-auto floating-scroll [mask-image:linear-gradient(to_bottom,black_95%,transparent)]"
        >
          <!-- Dynamic Component Support -->
          <component
            :is="component"
            v-if="component"
            v-bind="componentProps"
            v-on="componentEvents || {}"
            @close="closePage"
          />
          <!-- Default Slot Fallback -->
          <slot v-else />
        </div>

        <!-- Modal Footer Container (Teleport target & slot fallback) -->
        <div
          id="popUpFooter"
          class="modal-footer bottom-0 z-20 bg-white p-4 border-t border-slate-100 shrink-0 empty:hidden flex items-center justify-end gap-2.5"
        >
          <slot name="footer" />
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    subTitle: {
        type: String,
        default: null,
    },
    size: {
        type: String,
        default: 'md',
    },
    // Dynamic Vue Component to render inside body
    component: {
        type: [Object, Function],
        default: null,
    },
    // Props passed directly to dynamic component
    componentProps: {
        type: Object,
        default: () => ({}),
    },
    // Event handlers passed directly to dynamic component
    componentEvents: {
        type: Object,
        default: () => ({}),
    },
})

const emit = defineEmits(['close'])

const closePage = () => {
    emit('close')
}

const sizeClasses = {
    sm: 'max-w-md',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
    '2xl': 'max-w-5xl',
}
</script>
