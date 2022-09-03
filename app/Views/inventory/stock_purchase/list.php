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
                <div class="row">
                    <div class="col-md-12">
                        <a href="<?= base_url('inventory/stock_purchase/form') ?>" class="btn btn-success mb-2 float-right">
                            <div class="c-icon mr-1 cil-plus"></div>
                            <span>Pembelian Baru</span>
                        </a>
                    </div>
                </div>
                <?= startSearchForm('stock_purchase') ?>
                <div class="row">
                    <div class="col-md-9">
                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <div class="input-group-text">Supplier</div>
                            </div>
                            <input type="text" class="form-control" id="supplier-search" autocomplete="off">
                            <input type="hidden" name="supplier" id="supplier" class="form-filter">
                        </div>
                    </div>
                </div>
                <div class="row mb-2">
                    <?= $filter_form ?>
                </div>
                <?= endSearchForm() ?>
                <div class="table-responsive">
                    <?= loadDatatables("stock_purchase") ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection('main'); ?>
<?= $this->section('js'); ?>
<script>
    $(document).find('#supplier-search').typeahead({
        highlight: true,
        items: 15,
        source: function(query, result) {
            $.ajax({
                url: "<?= base_url('inventory/supplier/autocomplete') ?>",
                data: 'q=' + query,
                dataType: "json",
                type: "GET",
                success: function(data) {
                    $('#supplier').val("");
                    $('#supplier').trigger('change');
                    result($.map(data, function(item) {
                        return item;
                    }));
                }
            });
        },
        autoSelect: true,
        afterSelect: function(item) {
            $('#supplier').val(item.id);
            $('#supplier-search').val(item.name);

            // trigger change
            $('#supplier').trigger('change');
        }
    });
</script>
<?= $this->endSection('js'); ?>