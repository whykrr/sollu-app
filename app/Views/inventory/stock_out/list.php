<?= $this->extend('layout/general_layout'); ?>

<?= $this->section('breadcrumb'); ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb my-0 ms-2">
        <li class="breadcrumb-item"><span>Inventory</span></li>
        <li class="breadcrumb-item active"><span>Stok Keluar</span></li>
    </ol>
</nav>
<?= $this->endSection('breadcrumb'); ?>

<?= $this->section('main'); ?>
<div class="container-fluid">
    <div class="fade-in">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Data Stok Keluar</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <a href="<?= base_url('inventory/stock_out/form') ?>" class="btn btn-success mb-2 float-right">
                            <div class="c-icon mr-1 cil-plus"></div>
                            <span>Input Stok Keluar</span>
                        </a>
                    </div>
                </div>
                <?= startSearchForm('stock_out') ?>
                <div class="row mb-2"><?= $filter_form ?></div>
                <?= endSearchForm() ?>
                <div class="table-responsive">
                    <?= loadDatatables("stock_out") ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection('main'); ?>