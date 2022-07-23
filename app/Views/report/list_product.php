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
<div class="container-fluid">
    <div class="fade-in">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Laporan Produk</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">

                    </div>
                </div>
                <?= $filter_form ?>
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