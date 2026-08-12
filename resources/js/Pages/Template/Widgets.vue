<template>
    <MainPage>
        <div class="flex flex-col gap-2">
            <div class="grid grid-cols-4 gap-2">
                <div>
                    <Widget
                        title="Number of Students"
                        :icon="faUser"
                        class="widget-main"
                    >
                        2,468
                    </Widget>
                </div>
                <div>
                    <Widget
                        title="Number of Teachers"
                        :icon="faUsers"
                        class="widget-info"
                    >
                        245
                    </Widget>
                </div>
                <div>
                    <Widget
                        title="Number of Employees"
                        :icon="faUserGroup"
                        class="widget-teal"
                    >
                        526
                    </Widget>
                </div>
                <div>
                    <Widget
                        title="Total Revenue"
                        :icon="faDollarSign"
                        class="widget-brown"
                    >
                        $2,324,468
                    </Widget>
                </div>
                <div>
                    <WidgetProgress
                        title="Total Barang"
                        :icon="faDollarSign"
                        :value="90"
                        :max-value="100"
                        class="widget-main"
                    >
                        90 Pcs
                    </WidgetProgress>
                </div>
                <div>
                    <WidgetProgress
                        title="Total Revenue"
                        :icon="faDollarSign"
                        :value="2324468"
                        :max-value="3000000"
                        class="widget-secondary"
                    >
                        Rp. 2,324,468
                    </WidgetProgress>
                </div>
                <div>
                    <WidgetProgress
                        title="Total Transaksi"
                        :icon="faDollarSign"
                        :value="217"
                        :max-value="300"
                        class="widget-teal"
                    >
                        217 Transaksi
                    </WidgetProgress>
                </div>
                <div>
                    <WidgetProgress
                        title="Total Revenue"
                        :icon="faCircle"
                        :value="90"
                        :max-value="100"
                        class="widget-brown"
                    >
                        Rp. 2,324,468
                    </WidgetProgress>
                </div>
                <div class="col-span-2">
                    <WidgetChart
                        id="chart12"
                        :icon="faDollarSign"
                        class="widget-main"
                        type="line"
                        highlight="1268"
                        sub-highlight="266"
                        title="Transaction"
                        :labels="labels"
                        :data="data"
                    />
                </div>
                <div class="col-span-2">
                    <WidgetChart
                        id="chart13"
                        :icon="faDollarSign"
                        class="widget-secondary"
                        type="bar"
                        highlight="1268"
                        sub-highlight="266"
                        title="Transaction"
                        :labels="labels"
                        :data="data"
                    />
                </div>
            </div>
        </div>
    </MainPage>
</template>
<script setup>
import MainPage from '@/Components/UI/MainPage.vue';
import Widget from '@/Components/Widgets/Widget.vue';
import WidgetChart from '@/Components/Widgets/WidgetChart.vue';
import WidgetProgress from '@/Components/Widgets/WidgetProgress.vue';
import { faCircle } from '@fortawesome/free-regular-svg-icons';
import {
    faDollarSign,
    faUser,
    faUserGroup,
    faUsers,
} from '@fortawesome/free-solid-svg-icons';

const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'];
const data = [0, 0, 80, 360, 56, 155, 140, 220];
</script>
