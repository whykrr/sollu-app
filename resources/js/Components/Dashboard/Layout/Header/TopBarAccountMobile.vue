<template>
  <div ref="dropdownRef">
    <div
      class="bg-neutral-200/70 hover:p-1 hover:-m-1 rounded-full transition-all duration-150 ease-in-out"
      :class="{ 'p-1 -m-1': showPanel }"
    >
      <a href="#" class="text-slate-700" @click.prevent="togglePanel">
        <div
          class="rounded-full w-10 h-10 bg-white/90 flex items-center justify-center border"
        >
          {{ initials }}
        </div>
        <!-- <img
                    :src="
                        'https://ui-avatars.com/api/?name=' +
                        auth.name +
                        '&size=40&background=fff'
                    "
                    alt="Profile"
                    class="rounded-full w-9 h-9"
                /> -->
      </a>
    </div>
  </div>
  <div
    :class="{
      'translate-x-0': showPanel,
      'translate-x-full': !showPanel,
    }"
    class="fixed w-full h-full bg-gray-300/50 backdrop-blur-sm top-0 right-0 z-[70] p-4 transform transition-transform duration-300"
  >
    <div class="flex flex-col gap-2">
      <div class="absolute right-4">
        <a href="#" @click.prevent="closePanel">
          <FontAwesomeIcon :icon="faClose" />
        </a>
      </div>
      <div class="text-center text-sm font-medium">
        {{ auth.email }}
      </div>
      <div class="m-auto">
        <div
          class="rounded-full w-16 h-16 text-2xl bg-white flex items-center justify-center"
        >
          {{ initials }}
        </div>
        <!-- <img
                            :src="
                                'https://ui-avatars.com/api/?name=' +
                                auth.name +
                                '&size=40&background=fff'
                            "
                            alt="Profile"
                            class="rounded-full w-16 h-16"
                        /> -->
      </div>
      <div class="text-center text-xl">
        {{ auth.name }}
      </div>
      <div class="bg-white rounded-xl overflow-hidden p-2 space-y-2">
        <div class="flex flex-row gap-2 items-center">
          <div>
            <div
              class="w-20 aspect-square bg-secondary/30 rounded-lg"
            >
              <div
                v-if="!auth.merchant.logo"
                class="flex w-full h-full items-center justify-center"
              >
                <FontAwesomeIcon
                  :icon="faShop"
                  class="text-secondary-dark text-[30px]"
                />
              </div>
              <img
                v-else
                :src="auth.merchant.logo_url"
                alt="Logo"
                class="w-full h-full"
              />
            </div>
          </div>

          <div class="text-center text-xl">
            {{ auth.merchant.name }}
          </div>
        </div>
        <div
          v-if="!merchantInfo"
          class="grid grid-flow-row gap-1 animate-pulse"
        >
          <div class="placeholder w-[50%] mb-0" />
          <div class="placeholder w-[75%] mb-0" />
          <div class="placeholder w-[75%] mb-0" />
          <div class="placeholder w-[75%] mb-0" />
        </div>
        <div v-else class="grid grid-flow-row gap-1 text-sm">
          <div class="flex flex-row justify-between">
            <div class="font-medium">Jenis Usaha</div>
            <div>
              {{ merchantInfo.merchantType }}
            </div>
          </div>
          <div class="flex flex-row justify-between">
            <div class="font-medium">Langganan</div>
            <div>
              {{ merchantInfo.subscription.plan.name }}
            </div>
          </div>
          <div class="flex flex-row justify-between">
            <div class="font-medium">Aktif Sampai</div>
            <div>
              {{
                formatDateID(merchantInfo.subscription.end_date)
              }}
            </div>
          </div>
          <div class="flex flex-row justify-between">
            <div class="font-medium">Jumlah Outlet</div>
            <div>{{ merchantInfo.outlet_count }} Outlet</div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl overflow-hidden">
        <ol>
          <li v-for="(item, index) in merchantLinks" :key="index">
            <Link
              :href="item.link"
              class="flex items-center gap-2 px-3 py-2 hover:bg-neutral-200/50 text-sm transition-all duration-150 ease-in-out"
              :method="item.method"
            >
              <FontAwesomeIcon :icon="item.icon" />
              {{ item.label }}
            </Link>
          </li>
        </ol>
      </div>
      <div class="-mb-1 text-sm font-semibold">Akun</div>
      <div class="bg-white rounded-xl overflow-hidden">
        <ol>
          <li v-for="(item, index) in accountLinks" :key="index">
            <Link
              v-if="item.method == 'delete'"
              :href="item.link"
              class="flex items-center w-full gap-2 px-3 py-2 hover:bg-neutral-200/50 text-sm transition-all duration-150 ease-in-out"
              method="delete"
              as="button"
            >
              <FontAwesomeIcon
                :icon="item.icon"
              />
              {{ item.label }}
            </Link>
            <Link
              v-else
              :href="item.link"
              class="flex items-center gap-2 px-3 py-2 hover:bg-neutral-200/50 text-sm transition-all duration-150 ease-in-out"
            >
              <FontAwesomeIcon
                :icon="item.icon"
              />
              {{ item.label }}
            </Link>
          </li>
        </ol>
      </div>
    </div>
  </div>
</template>
<script setup>
import { formatDateID } from '@/helpers/Dashboard/date'
import {
    faClose,
    faCog,
    faCreditCard,
    faKey,
    faRightFromBracket,
    faShop,
    faUser,
} from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Link, router, usePage } from '@inertiajs/vue3'
import { method } from 'lodash'
import { computed, onBeforeMount, onMounted, ref, watch } from 'vue'

const auth = computed(() => usePage().props.auth)
const showPanel = ref(false)
const dropdownRef = ref(null)
const merchantInfo = ref(null)

const initials = computed(() => {
    const name = auth.value?.name || ''
    return name
        .split(' ')
        .map((word) => word[0])
        .join('')
        .substring(0, 2)
        .toUpperCase()
})

const togglePanel = () => {
    merchantInfo.value = null
    showPanel.value = !showPanel.value
}

const closePanel = () => {
    showPanel.value = false
}

const accountLinks = [
    {
        label: 'Info Akun',
        icon: faUser,
        link: '#',
        method: 'get',
    },
    {
        label: 'Ubah kata sandi',
        icon: faKey,
        link: '#',
    },
    {
        label: 'Keluar',
        icon: faRightFromBracket,
        link: route('dashboard.logout'),
        method: 'delete',
    },
]

const merchantLinks = [
    {
        label: 'Info Usaha',
        icon: faShop,
        link: route('dashboard.merchant.info.detail'),
        method: 'get',
    },
    {
        label: 'Langganan & Tagihan',
        icon: faCreditCard,
        link: route('dashboard.merchant.billing.index'),
        method: 'get',
    },
    {
        label: 'Pengaturan Usaha',
        icon: faCog,
        link: '#',
        method: 'get',
    },
]

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        showPanel.value = false
    }
}

watch(
    () => showPanel.value,
    (val) => {
        if (val) {
            router.reload({
                only: ['merchantInfo'],
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page) => {
                    merchantInfo.value = page.props.merchantInfo
                },
            })
        }
    },
)

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onBeforeMount(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>
