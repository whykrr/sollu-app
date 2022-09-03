<?= $this->extend('layout/general_layout'); ?>

<?= $this->section('breadcrumb'); ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb my-0 ms-2">
        <li class="breadcrumb-item active"><span>Penjualan</span></li>
    </ol>
</nav>
<?= $this->endSection('breadcrumb'); ?>

<?= $this->section('main'); ?>
<div class="container-fluid">
    <div class="fade-in">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Data Penjualan</h5>
            </div>
            <div class="card-body">
                <?= startSearchForm('sales') ?>
                <?php if (verifyPos('complex')) : ?>
                    <div class="row">
                        <div class="col-md-9">
                            <div class="input-group mb-2">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">Pelanggan</div>
                                </div>
                                <input type="text" class="form-control" id="pelanggan-search" autocomplete="off">
                                <input type="hidden" name="customer" id="pelanggan" class="form-filter">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="row mb-2"><?= $filter_form ?></div>
                <?= endSearchForm() ?>
                <div class="table-responsive">
                    <?= loadDatatables("sales") ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection('main'); ?>
<?= $this->section('js'); ?>
<script>
    $(document).find('#pelanggan-search').typeahead({
        highlight: true,
        items: 15,
        source: function(query, result) {
            $.ajax({
                url: "<?= base_url('customer/autocomplete') ?>",
                data: 'q=' + query,
                dataType: "json",
                type: "GET",
                success: function(data) {
                    $('#pelanggan').val("");
                    $('#pelanggan').trigger('change');
                    result($.map(data, function(item) {
                        return item;
                    }));
                }
            });
        },
        autoSelect: true,
        afterSelect: function(item) {
            $('#pelanggan').val(item.id);
            $('#pelanggan-search').val(item.name);

            // trigger change
            $('#pelanggan').trigger('change');
        }
    });
</script>
<?= $this->endSection('js'); ?>