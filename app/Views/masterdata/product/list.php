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
                <div class="row">
                    <div class="col-md-12">
                        <a class="btn btn-success mb-2" href="<?= base_url('masterdata/product/export') ?>">
                            <span>Export Data</span>
                        </a>
                        <button class="btn btn-primary mb-2" data-toggle="modal" data-target="#modalPrintBarcode">
                            <span>Print Barcode</span>
                        </button>
                        <button class="btn btn-success mb-2 float-right" data-toggle="modal" data-target="#modalSide" data-page="masterdata/product/form">
                            <div class="c-icon mr-1 cil-plus"></div>
                            <span>Tambah Data</span>
                        </button>
                    </div>
                </div>
                <?= startSearchForm('product') ?>
                <div class="row mb-2">
                    <div class="col-md-6">
                        <label for="search">Cari</label>
                        <input type="text" class="form-control" name="search" id="search" placeholder="Kode / Barcode / Nama" autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <label for="search">Kategori</label>
                        <select name="category_id" id="category_id" class="form-control">
                            <option value="">-- PILIH --</option>
                            <?php foreach ($category as $key => $value) : ?>
                                <option value="<?= $value['id'] ?>" <?= (@$edit['category_id'] == $value['id'] ? 'selected' : '') ?>><?= $value['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 align-self-end">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="c-icon cil-search"></i> Cari
                        </button>
                    </div>
                </div>
                <?= endSearchForm() ?>
                <div class="table-responsive">
                    <?= loadDatatables("product") ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection('main'); ?>