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
                <?= $filter_form ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <?php if ($filter['type_filter'] == 'monthly') : ?>
                                    <th colspan="4">Laporan Penjualan Periode <?= formatMonthID($filter['month']) ?> <?= $filter['year'] ?></th>
                                <?php elseif ($filter['type_filter'] == 'daily') : ?>
                                    <th colspan="4">Laporan Penjualan Tanggal <?= formatDateID($filter['date']) ?> </th>
                                <?php elseif ($filter['type_filter'] == 'range') : ?>
                                    <th colspan="4">Laporan Penjualan Periode <?= formatDateID($filter['start_date']) ?> s/d <?= formatDateID($filter['end_date']) ?></th>
                                <?php endif; ?>
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
                                <td colspan="4">Beban Lainnya</td>
                            </tr>
                            <?php foreach ($expenses_other as $ie) : ?>
                                <tr>
                                    <td></td>
                                    <td><?= $ie['description'] ?></td>
                                    <td class="text-right"><?= formatIDR($ie['amount']) ?></td>
                                    <td></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="2">Total Beban Lainnya</td>
                                <td colspan="2" class="text-right"><?= formatIDR($total_expenses_other) ?></td>
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