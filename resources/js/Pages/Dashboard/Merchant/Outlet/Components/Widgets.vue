<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
    <div class="widget widget-main">
      <div class="flex flex-row items-center gap-2">
        <div>
          <div class="widget-icon">
            <FontAwesomeIcon :icon="faCheck" />
          </div>
        </div>
        <h2 class="font-medium">Outlet Aktif</h2>
      </div>
      <div class="text-gray-8 font-bold">10 Outlet</div>
    </div>
    <div class="widget widget-main">
      <div class="flex flex-row items-center gap-2">
        <div>
          <div class="widget-icon">
            <FontAwesomeIcon :icon="faClose" />
          </div>
        </div>
        <h2 class="font-medium">Outlet Tidak Aktif</h2>
      </div>
      <div class="text-gray-8 font-bold">10 Outlet</div>
    </div>
  </div>
</template>
<script setup>
import { faCheck, faClose } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
</script>
