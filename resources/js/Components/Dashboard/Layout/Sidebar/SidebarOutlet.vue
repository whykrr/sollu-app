<template>
  <div ref="dropdownRef" class="">
    <div class="w-full">
      <div
        class="bg-white rounded-lg transition-all duration-150 ease-in-out mx-2 mt-0 mb-1 border"
        :class="{
          'hover:drop-shadow': outlets.length > 1,
          'drop-shadow': isOpen,
        }"
      >
        <a
          href="#"
          class="flex flex-row items-center min-h-11 px-2 gap-1.5"
          @click.prevent="selectOutlet"
        >
          <div
            class="flex items-center rounded-full text-sm bg-main/20 text-main h-[30px] w-[30px]"
          >
            <FontAwesomeIcon
              :icon="faMapMarkerAlt"
              class="m-auto"
            />
          </div>
          <div class="flex-1 font-medium text-sm truncate">
            <span v-if="selectedOutlet">{{
              selectedOutlet.name
            }}</span>
            <span v-else>Semua Outlet</span>
          </div>
          <div
            class="text-[10px] flex flex-col -space-y-0.5"
            :class="{ 'text-neutral-300': outlets.length === 1 }"
          >
            <FontAwesomeIcon :icon="faChevronUp" />
            <FontAwesomeIcon :icon="faChevronDown" />
          </div>
        </a>
        <div
          class="top-8 w-full rounded-b-lg bg-white overflow-hidden transition-all duration-500 ease-in-out"
          :class="isOpen ? 'max-h-40' : 'max-h-0'"
        >
          <div class="text-sm">
            <ol class="">
              <li>
                <Link
                  method="post"
                  :preserve-scroll="true"
                  :preserve-state="true"
                  as="button"
                  :href="route('dashboard.switch.all')"
                  class="hover:bg-neutral-light py-1.5 px-2 block w-full text-start"
                  :class="{
                    'bg-neutral-light': !selectedOutlet,
                  }"
                  aria-disabled="true"
                  @click="selectOutlet"
                >
                  Semua Outlet
                </Link>
              </li>
              <li v-for="(o, index) in outlets" :key="index">
                <Link
                  method="post"
                  :preserve-scroll="true"
                  :preserve-state="true"
                  as="button"
                  :href="
                    route('dashboard.switch.outlet', {
                      id: o.id,
                    })
                  "
                  class="hover:bg-neutral-light py-1.5 px-2 w-full text-start"
                  :class="{
                    'bg-neutral-light':
                      o.id === selectedOutlet?.id,
                  }"
                  @click="selectOutlet"
                >
                  {{ o.name }}
                </Link>
              </li>
            </ol>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import {
    faChevronDown,
    faChevronUp,
    faMapMarkedAlt,
    faMapMarkerAlt,
} from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Link, usePage } from '@inertiajs/vue3'
import { computed, onBeforeMount, onMounted, ref } from 'vue'

const outlets = usePage().props.auth.outlets
const selectedOutlet = computed(() => usePage().props.selectedOutlet)
const isOpen = ref(false)
const dropdownRef = ref(null)

const selectOutlet = () => {
    if (outlets.length > 1) {
        isOpen.value = !isOpen.value
    }
}

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isOpen.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onBeforeMount(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>
