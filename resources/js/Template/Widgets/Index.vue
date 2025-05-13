<template>
    <Card title="Widgets">
        <div class="grid grid-cols-4 gap-4">
            <div>
                <Widget
                    title="Number of Students"
                    icon="fa-person"
                    class="bg-main dark:bg-main-light"
                >
                    <p class="text-md">2,468</p>
                </Widget>
            </div>
            <div>
                <Widget
                    title="Number of Teachers"
                    icon="fa-users"
                    class="bg-clay dark:bg-clay-light"
                >
                    <p class="text-md">245</p>
                </Widget>
            </div>
            <div>
                <Widget
                    title="Number of Employees"
                    icon="fa-user-group"
                    class="bg-turquoise dark:bg-turquoise-light"
                >
                    <p class="text-md">526</p>
                </Widget>
            </div>
            <div>
                <Widget
                    title="Total Revenue"
                    icon="fa-dollar-sign"
                    class="bg-brown dark:bg-brown-light"
                >
                    <p class="text-md">$2,324,468</p>
                </Widget>
            </div>

            <div>
                <WidgetProgress
                    class="bg-main"
                    title="Widget Title"
                    value-string="99%"
                    value="723"
                    max-value="1000"
                    helper-text="Widget helper text"
                />
            </div>
            <div>
                <WidgetProgress
                    class="bg-clay"
                    title="Widget Title"
                    value-string="99%"
                    value="723"
                    max-value="1000"
                    helper-text="Widget helper text"
                />
            </div>
            <div>
                <WidgetProgress
                    class="bg-turquoise"
                    title="Widget Title"
                    value-string="99%"
                    value="723"
                    max-value="1000"
                    helper-text="Widget helper text"
                />
            </div>
            <div>
                <WidgetProgress
                    class="bg-brown"
                    title="Widget Title"
                    value-string="99%"
                    value="723"
                    max-value="1000"
                    helper-text="Widget helper text"
                />
            </div>

            <div>
                <WidgetChart
                    id="chart1"
                    class="bg-main"
                    type="line"
                    highlight="1268"
                    sub-highlight="+266"
                    title="Transaction"
                    :labels="labels"
                    :data="data"
                />
            </div>
            <div>
                <WidgetChart
                    id="chart2"
                    class="bg-clay"
                    type="bar"
                    highlight="1268"
                    sub-highlight="+266"
                    title="Transaction"
                    :labels="labels"
                    :data="data"
                />
            </div>
            <div>
                <WidgetChart
                    id="chart3"
                    class="bg-turquoise"
                    type="line"
                    highlight="1268"
                    sub-highlight="+266"
                    title="Transaction"
                    :labels="labels"
                    :data="data"
                />
            </div>
            <div>
                <WidgetChart
                    id="chart4"
                    class="bg-brown"
                    type="bar"
                    highlight="1268"
                    sub-highlight="+266"
                    title="Transaction"
                    :labels="labels"
                    :data="data"
                />
            </div>

            <div
                class="col-span-4 -mx-2 px-2 p-4 rounded-lg grid grid-cols-4 bg-neutral-200 gap-4"
            >
                <div>
                    <WidgetCard
                        icon="fa-dollar-sign"
                        title="BCA"
                        sub-title="12353312"
                        description="IDR 1.000.000"
                    />
                </div>
                <div>
                    <WidgetCard
                        icon="fa-dollar-sign"
                        title="BCA"
                        sub-title="12353312"
                        description="IDR 1.000.000"
                    />
                </div>
                <div>
                    <WidgetCard
                        icon="fa-dollar-sign"
                        title="BCA"
                        sub-title="12353312"
                        description="IDR 1.000.000"
                    />
                </div>
                <div>
                    <WidgetCard
                        icon="fa-dollar-sign"
                        title="BCA"
                        sub-title="12353312"
                        description="IDR 1.000.000"
                    />
                </div>
                <div>
                    <WidgetHighlight
                        title="IDR 100.000"
                        description="Description"
                        color="bg-main"
                    />
                </div>
                <div>
                    <WidgetHighlight
                        title="IDR 100.000"
                        description="Description"
                        color="bg-clay"
                    />
                </div>
                <div>
                    <WidgetHighlight
                        title="IDR 100.000"
                        description="Description"
                        color="bg-turquoise"
                    />
                </div>
                <div>
                    <WidgetHighlight
                        title="IDR 100.000"
                        description="Description"
                        color="bg-brown89"
                    />
                </div>
            </div>
        </div>
    </Card>
</template>
<script setup>
import Card from "@/Components/UI/Card.vue";
import Widget from "@/Components/UI/Widget.vue";
import WidgetCard from "@/Components/UI/WidgetCard.vue";
import WidgetChart from "@/Components/UI/WidgetChart.vue";
import WidgetHighlight from "@/Components/UI/WidgetHighlight.vue";
import WidgetProgress from "@/Components/UI/WidgetProgress.vue";

const labels = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Des",
];
const data = [65, 59, 80, 81, 56, 55, 40, 0, 0, 0, 0, 0];
</script>
