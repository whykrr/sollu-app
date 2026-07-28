<template>
  <div class="widget">
    <div class="flex flex-row justify-between items-start gap-3">
      <div class="flex flex-col gap-1">
        <h2 class="font-medium text-sm text-neutral-600">{{ title }}</h2>
        <div class="text-2xl font-bold text-neutral-800">
          {{ highlight }}
          <span class="inline-flex text-sm font-medium text-success items-center ml-1">
            <font-awesome-icon :icon="faPlus" class="text-xs mr-0.5" />
            <span>{{ subHighlight }}</span>
          </span>
        </div>
      </div>
      <div class="widget-icon bg-main/10 text-main shrink-0">
        <FontAwesomeIcon class="text-lg" :icon />
      </div>
    </div>
    <div class="-mx-4 -mb-4 mt-2">
      <canvas :id="'chart' + id" class="canvas w-full h-full" />
    </div>
  </div>
</template>
<script setup>
import { onMounted } from 'vue'
import { Chart } from 'chart.js/auto'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faArrowUp, faPlus } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    id: String,
    type: String,
    highlight: String,
    subHighlight: String,
    icon: String,
    title: String,
    labels: Array,
    data: Array,
})

const getColorFromClass = (className) => {
    const el = document.getElementById(`chart${props.id}`)
    const color = getComputedStyle(el).color
    return color
}

onMounted(() => {
    const borderColor = getColorFromClass(props.color)
    const backgroundColor = getColorFromClass(props.color)

    new Chart(document.getElementById('chart' + props.id), {
        type: props.type,
        data: {
            labels: props.labels,
            datasets: [
                {
                    data: props.data,
                    fill: true,
                    borderColor,
                    backgroundColor,
                    borderWidth: 3,
                    tension: 0.5,
                    pointRadius: 0,
                    borderRadius: 3,
                    spanGaps: true,
                },
            ],
        },
        options: {
            layout: {
                padding: {
                    top: 3,
                    bottom: 0,
                },
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    displayColors: false,
                },
            },
            scales: {
                x: {
                    display: false,
                },
                y: {
                    display: false,
                },
            },
        },
    })
})
</script>
