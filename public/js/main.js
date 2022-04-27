/* eslint-disable object-shorthand */

/* global Chart, coreui, coreui.Utils.getStyle, coreui.Utils.hexToRgba */

/**
 * --------------------------------------------------------------------------
 * CoreUI Boostrap Admin Template (v3.4.0): main.js
 * Licensed under MIT (https://coreui.io/license)
 * --------------------------------------------------------------------------
 */

/* eslint-disable no-magic-numbers */
// Disable the on-canvas tooltip
Chart.defaults.global.pointHitDetectionRadius = 1;
Chart.defaults.global.tooltips.enabled = false;
Chart.defaults.global.tooltips.mode = "index";
Chart.defaults.global.tooltips.position = "nearest";
Chart.defaults.global.tooltips.custom = coreui.ChartJS.customTooltips;
Chart.defaults.global.defaultFontColor = "#646470"; // eslint-disable-next-line no-unused-vars

// function ajax_chart(chart, url, data) {
//     var data = data || {};

//     $.getJSON(url, data).done(function (response) {
//         chart.data.labels = response.labels;
//         chart.data.datasets[0].data = response.data.quantity; // or you can iterate for multiple datasets
//         chart.update(); // finally update our chart
//     });
// }

// var cardChart1 = new Chart(document.getElementById("card-chart1"), {
//     type: "line",
//     data: {
//         labels: [
//             "January",
//             "February",
//             "March",
//             "April",
//             "May",
//             "June",
//             "July",
//         ],
//         datasets: [
//             {
//                 label: "My First dataset",
//                 backgroundColor: "transparent",
//                 borderColor: "rgba(255,255,255,.55)",
//                 pointBackgroundColor: coreui.Utils.getStyle("--primary"),
//                 data: [1, 18, 9, 17, 34, 22, 11],
//             },
//         ],
//     },
//     options: {
//         maintainAspectRatio: false,
//         legend: {
//             display: false,
//         },
//         scales: {
//             xAxes: [
//                 {
//                     gridLines: {
//                         color: "transparent",
//                         zeroLineColor: "transparent",
//                     },
//                     ticks: {
//                         fontSize: 2,
//                         fontColor: "transparent",
//                     },
//                 },
//             ],
//             yAxes: [
//                 {
//                     display: false,
//                     ticks: {
//                         display: false,
//                         min: -4,
//                         max: 39,
//                     },
//                 },
//             ],
//         },
//         elements: {
//             line: {
//                 tension: 0.00001,
//                 borderWidth: 1,
//             },
//             point: {
//                 radius: 4,
//                 hitRadius: 10,
//                 hoverRadius: 4,
//             },
//         },
//     },
// }); // eslint-disable-next-line no-unused-vars

// var cardChart2 = new Chart(document.getElementById("card-chart2"), {
//     type: "line",
//     data: {
//         labels: [
//             "January",
//             "February",
//             "March",
//             "April",
//             "May",
//             "June",
//             "July",
//         ],
//         datasets: [
//             {
//                 label: "My First dataset",
//                 backgroundColor: "transparent",
//                 borderColor: "rgba(255,255,255,.55)",
//                 pointBackgroundColor: coreui.Utils.getStyle("--info"),
//                 data: [1, 18, 9, 17, 34, 22, 11],
//             },
//         ],
//     },
//     options: {
//         maintainAspectRatio: false,
//         legend: {
//             display: false,
//         },
//         scales: {
//             xAxes: [
//                 {
//                     gridLines: {
//                         color: "transparent",
//                         zeroLineColor: "transparent",
//                     },
//                     ticks: {
//                         fontSize: 2,
//                         fontColor: "transparent",
//                     },
//                 },
//             ],
//             yAxes: [
//                 {
//                     display: false,
//                     ticks: {
//                         display: false,
//                         min: -4,
//                         max: 39,
//                     },
//                 },
//             ],
//         },
//         elements: {
//             line: {
//                 tension: 0.00001,
//                 borderWidth: 1,
//             },
//             point: {
//                 radius: 4,
//                 hitRadius: 10,
//                 hoverRadius: 4,
//             },
//         },
//     },
// }); // eslint-disable-next-line no-unused-vars

// var cardChart3 = new Chart(document.getElementById("card-chart3"), {
//     type: "line",
//     data: {
//         labels: [
//             "January",
//             "February",
//             "March",
//             "April",
//             "May",
//             "June",
//             "July",
//             "August",
//             "September",
//             "October",
//             "November",
//             "Desember",
//         ],
//         datasets: [
//             {
//                 label: "My First dataset",
//                 backgroundColor: "transparent",
//                 borderColor: "rgba(255,255,255,.55)",
//                 pointBackgroundColor: coreui.Utils.getStyle("--warning"),
//                 data: [0, 0, 0, 17, 34, 22, 11, 12, 29, 30, 11, 16],
//             },
//         ],
//     },
//     options: {
//         maintainAspectRatio: false,
//         legend: {
//             display: false,
//         },
//         scales: {
//             xAxes: [
//                 {
//                     gridLines: {
//                         color: "transparent",
//                         zeroLineColor: "transparent",
//                     },
//                     ticks: {
//                         fontSize: 2,
//                         fontColor: "transparent",
//                     },
//                 },
//             ],
//             yAxes: [
//                 {
//                     display: false,
//                     ticks: {
//                         display: false,
//                         min: -4,
//                         max: 39,
//                     },
//                 },
//             ],
//         },
//         elements: {
//             line: {
//                 tension: 0.00001,
//                 borderWidth: 1,
//             },
//             point: {
//                 radius: 4,
//                 hitRadius: 10,
//                 hoverRadius: 4,
//             },
//         },
//     },
// }); // eslint-disable-next-line no-unused-vars

//# sourceMappingURL=main.js.map
