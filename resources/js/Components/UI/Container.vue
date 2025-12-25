<template>
  <div class="flex flex-col gap-2 h-full">
    <div v-if="$slots.widgets">
      <slot name="widgets" />
    </div>
    <div
      v-if="$slots.header"
      class="flex flex-row justify-between gap-2 items-center"
    >
      <slot name="header" />
    </div>
    <div
      class="flex-1 overflow-scroll [&::-webkit-scrollbar]:w-0 [&::-webkit-scrollbar]:h-0"
    >
      <slot />
    </div>
    <div v-if="$slots.footer" class="">
      <slot name="footer" />
    </div>
  </div>
</template>
<script setup></script>
