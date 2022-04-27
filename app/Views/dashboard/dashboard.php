<?= $this->extend('layout/general_layout'); ?>

<?= $this->section('breadcrumb'); ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb my-0 ms-2">
        <li class="breadcrumb-item active"><span>Dashboard</span></li>
    </ol>
</nav>
<?= $this->endSection('breadcrumb'); ?>

<?= $this->section('main'); ?>
<div class="container-fluid">
    <div class="fade-in">
        <div class="row">
            <div class="col-sm-6 col-lg-6">
                <div class="card text-white bg-primary">
                    <div class="card-body card-body pb-0 d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-value-lg"><?= $flash['today_sales'] ?></div>
                            <div>Total Transaksi Penjualan Hari Ini</div>
                        </div>
                    </div>
                    <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
                        <canvas class="chart" id="card-chart1" height="70"></canvas>
                    </div>
                </div>
            </div>
            <!-- /.col-->
            <div class="col-sm-6 col-lg-6">
                <div class="card text-white bg-info">
                    <div class="card-body card-body pb-0 d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-value-lg"><?= formatIDR($flash['today_income']) ?></div>
                            <div>Total Omzet Hari Ini</div>
                        </div>
                    </div>
                    <div class="c-chart-wrapper mt-3 mx-3" style="height:70px;">
                        <canvas class="chart" id="card-chart2" height="70"></canvas>
                    </div>
                </div>
            </div>
            <!-- /.col-->
        </div>
        <!-- /.row-->
        <div class="card">
            <div class="card-body">
                <div class="d-flex">
                    <div>
                        <h4 class="card-title mb-0">Grafik Omzet</h4>
                        <div class="small text-muted"><?= formatMonthID(date('m')) . ' ' . date('Y') ?></div>
                    </div>
                </div>
                <div class="c-chart-wrapper p-2">
                    <canvas class="chart" id="main-chart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection('main'); ?>
<?= $this->section('js'); ?>
<script>
    labelsResult = <?= json_encode($flash['monthly_income']['labels']) ?>;
    dataResult = <?= json_encode($flash['monthly_income']['data']) ?>;

    var mainChart = new Chart(document.getElementById("main-chart"), {
        type: "line",
        data: {
            labels: labelsResult,
            datasets: [{
                label: "Tes",
                backgroundColor: "transparent",
                borderColor: coreui.Utils.getStyle("--info"),
                pointHoverBackgroundColor: "#fff",
                borderWidth: 2,
                data: dataResult,
            }, ],
        },
        options: {
            maintainAspectRatio: false,
            legend: {
                display: false,
            },
            scales: {
                xAxes: [{
                    gridLines: {
                        drawOnChartArea: false,
                    },

                }, ],
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        maxTicksLimit: 5,
                        callback: function(value, index, ticks) {
                            return formatRupiah(value.toString(), true);
                        }
                    },
                }, ],
            },
            elements: {
                line: {
                    borderWidth: 2,
                    tension: 0.00001,
                },
                point: {
                    radius: 4,
                    hitRadius: 10,
                    hoverRadius: 4,
                },
            },
            tooltips: {
                intersect: false,
                callbacks: {
                    label: function(tooltipItem, data) {
                        return formatRupiah(tooltipItem.yLabel.toString(), true);
                    },
                },
            },
        },
    });
</script>
<?= $this->endSection('js'); ?>