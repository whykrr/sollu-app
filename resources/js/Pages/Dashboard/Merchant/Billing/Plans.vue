<template>
  <Container>
    <div
      v-if="invoice"
      class="alert alert-warning inline-flex justify-between items-center w-full mb-2"
    >
      <div class="text-sm">
        <div class="font-bold">
          Tagihan Anda masih menunggu pembayaran.
        </div>
        Silakan lakukan pembayaran atau batalkan untuk mengganti paket
        langganan.
      </div>
      <div>
        <Link
          :href="route('dashboard.merchant.invoices.index')"
          class="btn btn-main btn-sm"
        >
          Bayar Tagihan
        </Link>
      </div>
    </div>
    <div
      class="inline-flex justify-between w-full mb-2 rounded-lg bg-white border p-2"
    >
      <div class="text-sm">
        <div class="font-bold">Lebih Hemat & Praktis</div>
        Bayar 10 bulan, nikmati layanan 12 bulan penuh. Dapatkan hingga
        2 bulan gratis dengan paket tahunan!
      </div>
      <Switch
        id="switch_regular"
        v-model="yearly"
        name="switch_regular"
        labeling="Tahunan"
      />
    </div>

    <div class="grid grid-flow-col gap-2">
      <div v-for="(plan, index) in plans" :key="index">
        <div
          class="p-2 space-y-2 rounded-lg border bg-white overflow-hidden"
          :class="{
            'border-main/50':
              subscription.subscription_plans_id === plan.id,
          }"
        >
          <div class="flex flex-col gap-1 pt-0 h-full">
            <div class="space-y-2">
              <div
                class="bg-gradient-to-br from-main to-secondary-dark rounded-md p-2 text-white relative"
              >
                <div class="font-semibold text-3xl">
                  {{ plan.name }}
                </div>
                <div class="text-sm">
                  {{ plan.description }}
                </div>

                <div
                  v-if="
                    subscription.subscription_plans_id ===
                      plan.id
                  "
                  class="absolute p-2 right-0 bottom-0 rounded-l-md"
                >
                  <FontAwesomeIcon :icon="faCheckCircle" />
                </div>
              </div>

              <div>
                <div class="font-medium text-2xl">
                  {{ formatIDR(plan.price) }}
                </div>
                <div v-if="yearly" class="text-sm">
                  per tahun/outlet
                </div>
                <div v-else class="text-sm">
                  per bulan/outlet
                </div>
              </div>
            </div>
            <div class="flex-1 mt-2">
              <ul class="space-y-1">
                <li v-for="(feature, fIndex) in plan.features" :key="fIndex">
                  <div class="inline-flex gap-2 items-start">
                    <div>
                      <FontAwesomeIcon
                        :icon="faCheck"
                        class="text-xl text-success"
                      />
                    </div>
                    <div class="text-sm">
                      <div
                        class="font-semibold text-main"
                      >
                        {{ feature.title }}
                      </div>
                      <div>
                        {{ feature.detail }}
                      </div>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
            <div class="mt-2">
              <Link
                v-if="
                  !invoice &&
                    subscription.subscription_plans_id !==
                    plan.id
                "
                disabled
                :href="
                  route(
                    'dashboard.merchant.billing.subscribe',
                    { plan: plan.id }
                  )
                "
                class="btn btn-neutral-800 w-full justify-between"
              >
                Pilih Paket
                <FontAwesomeIcon :icon="faArrowRight" />
              </Link>
              <div
                v-else
                class="bg-gradient-to-br from-main to-secondary-dark text-white text-center p-2 py-3 -m-2"
              >
                Paket Saat Ini
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Container>
</template>
<script setup>
import Switch from '@/Components/Dashboard/Form/Switch.vue'
import Modal from '@/Components/Dashboard/Notifications/Modal.vue'
import Container from '@/Components/Dashboard/UI/Container.vue'
import { formatIDR } from '@/helpers/Dashboard/currency-format'
import {
    faArrowRight,
    faCheck,
    faCheckCircle,
} from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps({
    subscription: Object,
    billing_cycle: String,
    plans: Array,
    invoice: Object,
})

const yearly = ref(props.billing_cycle === 'yearly' ? true : false)

watch(yearly, (newVal) => {
    const cycle = newVal ? 'yearly' : 'monthly'
    router.reload({
        data: { billing_cycle: cycle },
        preserveState: true,
        replace: true,
        only: ['plans'],
    })
})
</script>
