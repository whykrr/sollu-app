<form action="<?= base_url('inventory/stock_spoil/save') ?>" class="ajax modal-content">
    <div class="modal-header">
        <h4 class="modal-title">Tambah Data Stok Terbuang</h4>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12 mb-2">
                <label for="name" class="mb-1">Produk</label>
                <input type="hidden" name="product_id" id="product_id">
                <input type="text" class="form-control" id="product" placeholder="Produk" autocomplete="off">
                <input type="hidden" name="cogs" id="cogs">
                <input type="hidden" name="qty_old" id="qty_old">
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-12 mb-2">
                <label for="qty" class="mb-1">Qty</label>
                <input type="number" id="qty" name="qty" class="form-control" placeholder="Qty">
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-12 mb-2">
                <label for="note" class="mb-1">Catatan</label>
                <textarea class="form-control" id="note" name="note" placeholder="Catatan"></textarea>
                <div class="invalid-feedback"></div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-danger" type="button" data-dismiss="modal">Close</button>
        <button class="btn btn-success float-right" type="submit">Simpan</button>
    </div>
</form>
<script>
    $('#product').typeahead({
        items: 15,
        source: function(query, result) {
            $.ajax({
                url: "<?= base_url('masterdata/product/autocomplete') ?>",
                data: 'q=' + query,
                dataType: "json",
                type: "GET",
                success: function(data) {
                    result($.map(data, function(item) {
                        return item;
                    }));
                }
            });
        },
        fitToElement: false,
        autoSelect: true,
        afterSelect: function(item) {
            $('#product_id').val(item.id);
            $('#cogs').val(parseInt(item.cogs));
            $('#qty_old').val(item.stock);
        }
    });
</script>