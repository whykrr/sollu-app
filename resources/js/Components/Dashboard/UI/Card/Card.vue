<template>
  <div class="card border p-2 space-y-2">
    <div v-if="image">
      <img
        :src="image"
        alt="Card Image"
        class="block-inline w-full rounded"
      />
    </div>
    <div class="flex flex-col gap-1 pt-0">
      <div>
        <h2 class="text-base font-semibold">{{ title }}</h2>
        <div class="text-sm">
          <slot />
        </div>
      </div>
      <div
        v-if="$slots.footer"
        class="flex flex-row gap-2 justify-between items-center"
      >
        <slot name="footer" />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
    image: String,
    title: String,
})
</script>
