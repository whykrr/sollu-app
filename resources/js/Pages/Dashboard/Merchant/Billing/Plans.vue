<template>
    <Container>
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
                name="switch_regular"
                labeling="Tahunan"
                v-model="yearly"
            />
        </div>
        <div class="grid grid-flow-col gap-2">
            <div v-for="plan in plans">
                <div class="card p-2 space-y-2 rounded-lg border">
                    <div class="flex flex-col gap-1 pt-0">
                        <div class="space-y-2">
                            <div class="font-semibold text-3xl text-main">
                                {{ plan.name }}
                            </div>
                            <div class="text-sm min-h-[150px]">
                                {{ plan.description }}
                            </div>
                            <div>
                                <div class="font-medium text-2xl">
                                    {{ formatIDR(plan.price) }}
                                </div>
                                <div class="text-sm" v-if="yearly">
                                    per tahun/outlet
                                </div>
                                <div class="text-sm" v-else>
                                    per bulan/outlet
                                </div>
                            </div>
                        </div>
                        <div
                            class="flex flex-row gap-2 justify-between items-center"
                        >
                            <Link
                                :href="
                                    route(
                                        'dashboard.merchant.billing.subscribe',
                                        { plan: plan.id }
                                    )
                                "
                                class="btn btn-main w-full justify-between"
                            >
                                Pilih Paket
                                <FontAwesomeIcon :icon="faArrowRight" />
                            </Link>
                        </div>
                        <div class="mt-2">
                            <ul class="space-y-1">
                                <li v-for="feature in plan.features">
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
                    </div>
                </div>
            </div>
        </div>
    </Container>
</template>
<script setup>
import Switch from "@/Components/Dashboard/Form/Switch.vue";
import Modal from "@/Components/Dashboard/Notifications/Modal.vue";
import Container from "@/Components/Dashboard/UI/Container.vue";
import { formatIDR } from "@/helpers/Dashboard/currency-format";
import { faArrowRight, faCheck } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { Link, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";

const props = defineProps({
    billing_cycle: String,
    plans: Array,
});

const yearly = ref(props.billing_cycle === "yearly" ? true : false);

watch(yearly, (newVal) => {
    const cycle = newVal ? "yearly" : "monthly";
    router.reload({
        data: { billing_cycle: cycle },
        preserveState: true,
        replace: true,
        only: ["plans"],
    });
});
</script>
