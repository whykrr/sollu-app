<?= $this->extend('layout/general_layout'); ?>

<?= $this->section('breadcrumb'); ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb my-0 ms-2">
        <li class="breadcrumb-item"><span>Inventory</span></li>
        <li class="breadcrumb-item active"><span>Stok Terbuang</span></li>
    </ol>
</nav>
<?= $this->endSection('breadcrumb'); ?>

<?= $this->section('main'); ?>
<div class="container-fluid">
    <div class="fade-in">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Data Stok Terbuang</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <button class="btn btn-success mb-2 float-right" data-toggle="modal" data-target="#modalSide" data-page="inventory/stock_spoil/form">
                            <div class="c-icon mr-1 cil-plus"></div>
                            <span>Tambah Data</span>
                        </button>
                    </div>
                </div>

                <?= startSearchForm('stock_spoil') ?>
                <div class="row mb-2"><?= $filter_form ?></div>
                <?= endSearchForm() ?>
                <div class="table-responsive">
                    <?= loadDatatables("stock_spoil") ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection('main'); ?>