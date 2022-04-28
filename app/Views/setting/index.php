<?= $this->extend('layout/general_layout'); ?>

<?= $this->section('breadcrumb'); ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb my-0 ms-2">
        <li class="breadcrumb-item active"><span>Pengaturan</span></li>
    </ol>
</nav>
<?= $this->endSection('breadcrumb'); ?>

<?= $this->section('main'); ?>
<div class="container-fluid">
    <div class="fade-in">
        <?php foreach ($category as $c) : ?>
            <form action="<?= base_url('setting/save') ?>" method="POST">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Pengaturan <?= ucwords($c) ?></h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($data as $setting) : ?>
                            <?php if ($setting['category'] == $c) : ?>
                                <div class="form-group row">
                                    <label for="<?= $setting['id'] ?>" class="col-sm-4 col-form-label"><?= $setting['name'] ?></label>
                                    <div class="col-sm-8">
                                        <input type="hidden" name="id[]" value="<?= $setting['id'] ?>">
                                        <input type="text" name="value[]" class="form-control" id="<?= $setting['id'] ?>" value="<?= $setting['value'] ?>">
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary float-right">Simpan</button>
                    </div>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</div>
<?= $this->endSection('main'); ?>