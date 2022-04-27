<?= $this->extend('layout/general_layout'); ?>

<?= $this->section('breadcrumb'); ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb my-0 ms-2">
        <li class="breadcrumb-item"><span>Inventory</span></li>
        <li class="breadcrumb-item active"><span>Pembelian Stok</span></li>
    </ol>
</nav>
<?= $this->endSection('breadcrumb'); ?>

<?= $this->section('main'); ?>
<div class="container-fluid">
    <div class="fade-in">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Data Pembelian Stok</h5>
            </div>
            <div class="card-body">
                <a href="<?= base_url('inventory/stock_purchase/form') ?>" class="btn btn-success mb-2 float-right">
                    <div class="c-icon mr-1 cil-plus"></div>
                    <span>Pembelian Baru</span>
                </a>
                <div class="table-responsive">
                    <?= loadDatatables("stock_purchase") ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection('main'); ?>