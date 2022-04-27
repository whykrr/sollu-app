<?= $this->extend('layout/general_layout'); ?>

<?= $this->section('breadcrumb'); ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb my-0 ms-2">
        <li class="breadcrumb-item"><span>Laporan</span></li>
        <li class="breadcrumb-item active"><span>Penjualan</span></li>
    </ol>
</nav>
<?= $this->endSection('breadcrumb'); ?>

<?= $this->section('main'); ?>
<div class="container-fluid">
    <div class="fade-in">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Data Laporan Penjualan</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th colspan="4">Laporan Penjualan Bulan April</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="2">Penjualan</td>
                                <td colspan="2" class="text-right"><?= formatIDR($sales) ?></td>
                            </tr>
                            <tr>
                                <td colspan="2">Harga Pokok Penjualan</td>
                                <td colspan="2" class="text-right"><?= formatIDR($cogs_sales) ?></td>
                            </tr>
                            <tr>
                                <td colspan="2">Laba Kotor</td>
                                <td colspan="2" class="text-right"><?= formatIDR($gross_profit) ?></td>
                            </tr>
                            <tr>
                                <td colspan="4">Beban Operasional</td>
                            </tr>
                            <?php foreach ($expenses as $ie) : ?>
                                <tr>
                                    <td></td>
                                    <td><?= $ie['description'] ?></td>
                                    <td class="text-right"><?= formatIDR($ie['amount']) ?></td>
                                    <td></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="2">Total Beban Operasional</td>
                                <td colspan="2" class="text-right"><?= formatIDR($total_expenses) ?></td>
                            </tr>
                            <tr>
                                <td colspan="2">Laba Penjualan</td>
                                <td colspan="2" class="text-right"><?= formatIDR($net_profit) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection('main'); ?>