<template>
  <div class="rounded-lg w-full h-full overflow-hidden shadow-xl relative">
    <a
      href="#"
      class="btn bg-main/30 rounded-full text-white absolute right-4 top-4"
    >
      Kembali ke website
      <FontAwesomeIcon :icon="faArrowRight" />
    </a>
    <img
      class="h-full w-full object-cover"
      src="img/sollu-verse.png"
      alt="banner"
    />
  </div>
</template>
<script setup lang="ts">
import { faArrowRight } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
</script>
