<?= $this->extend('layout/general_layout'); ?>

<?= $this->section('breadcrumb'); ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb my-0 ms-2">
        <li class="breadcrumb-item"><span>Hutang Piutang</span></li>
        <li class="breadcrumb-item active"><span>Hutang</span></li>
    </ol>
</nav>
<?= $this->endSection('breadcrumb'); ?>

<?= $this->section('main'); ?>
<div class="container-fluid">
    <div class="fade-in">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Data Hutang</h5>
            </div>
            <div class="card-body">
                <!-- <button class="btn btn-success mb-2 float-right" data-toggle="modal" data-target="#modalSide" data-page="masterdata/unit/form">
                    <div class="c-icon mr-1 cil-plus"></div>
                    <span>Tambah Data</span>
                </button> -->
                <div class="table-responsive">
                    <?= loadDatatables("debt") ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection('main'); ?>