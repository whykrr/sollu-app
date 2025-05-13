<template>
    <div class="flex flex-col md:grid md:grid-cols-2 lg:grid-cols-4 gap-2 mb-4">
        <Widget
            title="Number of Students"
            icon="fa-person"
            class="bg-main dark:bg-main-light"
        >
            <p class="text-md">2,468</p>
        </Widget>

        <Widget
            title="Number of Teachers"
            icon="fa-users"
            class="bg-clay dark:bg-clay-light"
        >
            <p class="text-md">245</p>
        </Widget>

        <Widget
            title="Number of Employees"
            icon="fa-user-group"
            class="bg-turquoise dark:bg-turquoise-light"
        >
            <p class="text-md">526</p>
        </Widget>

        <Widget
            title="Total Revenue"
            icon="fa-dollar-sign"
            class="bg-brown dark:bg-brown-light"
        >
            <p class="text-md">$2,324,468</p>
        </Widget>
    </div>

    <div class="grid grid-flow-row lg:grid-cols-4 gap-4 mb-4">
        <div class="col-span-4 lg:col-span-2">
            <Card title="Charts">
                <canvas id="chart-line"></canvas>
            </Card>
        </div>
        <div class="col-span-4 lg:col-span-2">
            <Card title="Bar">
                <canvas id="chart-bar"></canvas>
            </Card>
        </div>
    </div>

    <div class="flex flex-col">
        <Card title="Data List">
            <template #buttons>
                <button class="btn btn-main text-sm">
                    <fa icon="fa-plus" />
                    Create
                </button>
                <button class="btn btn-warning text-sm">
                    <fa icon="fa-file" />
                    Draft
                </button>
                <button class="btn btn-danger text-sm">
                    <fa icon="fa-trash" />
                    Trash
                </button>
            </template>
            <div class="grid grid-cols-3 gap-2">
                <div>
                    <div class="form-group">
                        <label for="form_group">
                            <fa icon="fa-search" />
                        </label>
                        <input
                            type="text"
                            placeholder="Search ..."
                            name=""
                            id=""
                            class="form-sm"
                        />
                    </div>
                </div>
                <div class="col-span-2">
                    <div class="flex flex-row gap-3 justify-end">
                        <div class="form-group-input sm">
                            <select name="" id="" class="form-sm">
                                <option>Name</option>
                                <option>Class</option>
                                <option>Subject</option>
                            </select>
                            <select name="" id="" class="form-sm">
                                <option>Asc</option>
                                <option>Desc</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="form_group">
                                <fa icon="fa-tag" />
                            </label>
                            <select name="" id="" class="form-sm">
                                <option>Status</option>
                                <option>Active</option>
                                <option>Not Active</option>
                            </select>
                        </div>
                        <div class="btn-group">
                            <input
                                type="radio"
                                class="btn-check peer"
                                v-model="isListView"
                                :value="true"
                                id="listViewRadio"
                            />
                            <label
                                class="btn btn-highlight-main dark:btn-highlight-main-light"
                                for="listViewRadio"
                            >
                                <fa icon="fa-list" />
                            </label>

                            <input
                                type="radio"
                                class="btn-check peer"
                                v-model="isListView"
                                :value="false"
                                id="gridViewRadio"
                            />
                            <label
                                class="btn btn-highlight-main dark:btn-highlight-main-light"
                                for="gridViewRadio"
                            >
                                <fa icon="fa-border-all" />
                            </label>
                        </div>
                    </div>
                </div>
                <div></div>
            </div>
            <ListView v-if="isListView" />
            <GridView v-else />
            <Pagination :links="pagination" :from="11" :to="20" :total="200" />
        </Card>
    </div>
</template>

<script setup>
import Search from "@/Components/Form/Search.vue";
import Card from "@/Components/UI/Card.vue";
import Pagination from "@/Components/UI/Pagination.vue";
import ListView from "@/Pages/Dashboard/Components/ListView.vue";
import Widget from "@/Components/UI/Widget.vue";
import { onMounted, ref } from "vue";
import DataViewSwitcher from "./Components/DataViewSwitcher.vue";
import GridView from "./Components/GridView.vue";
import { Chart } from "chart.js/auto";

const isListView = ref(true);

const pagination = [
    {
        url: "http://learning-vilt.test/realtor/listing?deleted=0&page=1",
        label: "&laquo; Previous",
        active: false,
    },
    {
        url: "http://sollu-cms.test",
        label: "1",
        active: true,
    },
    {
        url: "http://learning-vilt.test/realtor/listing?deleted=0&page=2",
        label: "2",
        active: false,
    },
    {
        url: "http://learning-vilt.test/realtor/listing?deleted=0&page=2",
        label: "Next &raquo;",
        active: false,
    },
];

const labels = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul"];
const data = {
    labels: labels,
    datasets: [
        {
            label: "English Class",
            data: [65, 59, 80, 81, 56, 55, 40],
            fill: false,
            borderColor: "rgb(0 74 173)",
            backgroundColor: "rgb(0 74 173)",
            tension: 0.3,
        },
        {
            label: "Korea Class",
            data: [65, 2, 56, 90, 12, 67, 88],
            fill: false,
            borderColor: "rgb(93 224 230)",
            backgroundColor: "rgb(93 224 230)",
            tension: 0.3,
        },
    ],
};

onMounted(() => {
    new Chart(document.getElementById("chart-line"), {
        type: "line",
        data: data,
    });
    new Chart(document.getElementById("chart-bar"), {
        type: "bar",
        data: data,
    });
});
</script>
