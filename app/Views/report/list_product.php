<?= $this->extend('layout/general_layout'); ?>

<?= $this->section('breadcrumb'); ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb my-0 ms-2">
        <li class="breadcrumb-item"><span>Laporan</span></li>
        <li class="breadcrumb-item active"><span>Produk</span></li>
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
                <h5 class="card-title mb-0">Laporan Produk</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <a class="btn btn-success mb-2 float-right" href="<?= base_url("report/product/export?month=$filter[month]&year=$filter[year]") ?>">
                            <span>Export Data</span>
                        </a>
                    </div>
                </div>
                <form method="post" action="<?= base_url('report/product') ?>" class="row mb-4">
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
                        <a class="btn btn-secondary btn-block" id="btn-filter" href="<?= base_url('report/product') ?>">Reset</a>
                    </div>
                    <div class="col-md-2 align-self-end pl-0">
                        <button type="submit" class="btn btn-primary btn-block" id="btn-filter">Cari</button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="datatatable table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Produk</th>
                                <th>Harga Jual</th>
                                <th>Total Penjualan</th>
                            </tr>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($report as $r) : ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= $r['code'] . ' - ' . $r['name']; ?></td>
                                    <td><?= formatIDR($r['selling_price']); ?></td>
                                    <td><?= $r['total_sales'] . ' ' . $r['unit_name']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection('main'); ?>