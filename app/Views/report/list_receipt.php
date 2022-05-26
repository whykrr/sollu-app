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
<?php
$filter['month'] = @$filter['month'] ?: date('m');
$filter['year'] = @$filter['year'] ?: date('Y');
?>
<div class="container-fluid">
    <div class="fade-in">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Data Laporan Penjualan</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?= base_url('report/receipt') ?>" class="row mb-4">
                    <div class="col-md-4">
                        <label for="month">Bulan</label>
                        <select name="month" id="month" class="form-control">
                            <?php foreach (getMonthIndo() as $key => $item) : ?>
                                <?php if ($filter['month'] == $key) : ?>
                                    <option value="<?= $key ?>" selected><?= $item ?></option>
                                <?php else : ?>
                                    <option value="<?= $key ?>"><?= $item ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="year">Tahun</label>
                        <select name="year" id="year" class="form-control">
                            <?php foreach ($filter_year as $key => $item) : ?>
                                <?php if ($filter['year'] == $key) : ?>
                                    <option value="<?= $item['year'] ?>" selected><?= $item['year'] ?></option>
                                <?php else : ?>
                                    <option value="<?= $item['year'] ?>"><?= $item['year'] ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 align-self-end">
                        <a class="btn btn-secondary btn-block" id="btn-filter" href="<?= base_url('report/receipt') ?>">Reset</a>
                    </div>
                    <div class="col-md-2 align-self-end pl-0">
                        <button type="submit" class="btn btn-primary btn-block" id="btn-filter">Cari</button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th colspan="4">Laporan Penjualan Periode <?= formatMonthID($filter['month']) ?> <?= $filter['year'] ?></th>
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