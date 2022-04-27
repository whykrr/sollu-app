<?= $this->extend('layout/general_layout'); ?>

<?= $this->section('breadcrumb'); ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb my-0 ms-2">
        <li class="breadcrumb-item"><span>Master Data</span></li>
        <li class="breadcrumb-item active"><span>Produk</span></li>
    </ol>
</nav>
<?= $this->endSection('breadcrumb'); ?>

<?= $this->section('main'); ?>
<div class="container-fluid">
    <div class="fade-in">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Data Produk</h5>
            </div>
            <div class="card-body">
                <button class="btn btn-success mb-2 float-right" data-toggle="modal" data-target="#modalSide" data-page="masterdata/product/form">
                    <div class="c-icon mr-1 cil-plus"></div>
                    <span>Tambah Data</span>
                </button>
                <div class="table-responsive">
                    <?= loadDatatables("product") ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection('main'); ?>