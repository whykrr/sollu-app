<form action="<?= base_url('masterdata/product/save') ?>" class="ajax modal-content">
    <?php if (@$edit['id']) : ?>
        <input type="hidden" name="id" value="<?= $edit['id'] ?>">
    <?php endif; ?>
    <div class="modal-header">
        <h4 class="modal-title"><?= (!@$edit['id'] ? 'Tambah' : '') ?> Data Produk</h4>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-6 mb-2 mr-0">
                <label for="name" class="mb-1">Kode</label>
                <input type="text" class="form-control" id="code" name="code" placeholder="Kode" value="<?= @$edit['code'] ?>">
                <small id="codeHelp" class="form-text text-muted">Kode akan otomatis dibuat jika dikosongkan.</small>
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-6 mb-2 mr-0">
                <label for="name" class="mb-1">Barcode</label>
                <input type="text" class="form-control" id="barcode" name="barcode" placeholder="Barcode" value="<?= @$edit['barcode'] ?>">
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-12 mb-2">
                <label for="name" class="mb-1">Nama</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Nama" value="<?= @$edit['name'] ?>">
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-6 mb-2 mr-0">
                <label for="name" class="mb-1">Kategori</label>
                <select name="category_id" id="category_id" class="form-control">
                    <option value="">-- PILIH --</option>
                    <?php foreach ($category as $key => $value) : ?>
                        <option value="<?= $value['id'] ?>" <?= (@$edit['category_id'] == $value['id'] ? 'selected' : '') ?>><?= $value['name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-6 mb-2">
                <label for="name" class="mb-1">Satuan</label>
                <select name="unit_id" id="unit_id" class="form-control">
                    <option value="">-- PILIH --</option>
                    <?php foreach ($unit as $key => $value) : ?>
                        <option value="<?= $value['id'] ?>" <?= (@$edit['unit_id'] == $value['id'] ? 'selected' : '') ?>><?= $value['name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-12 mb-2">
                <label for="name" class="mb-1">Stok Minimal</label>
                <input type="number" class="form-control" id="stock_min" name="stock_min" placeholder="Harga Pokok" value="<?= (isset($edit['stock_min'])) ? intval($edit['stock_min']) : '0' ?>" autocomplete="off">
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-6 mb-2 mr-0">
                <label for="name" class="mb-1">Harga Pokok</label>
                <input type="currency" class="form-control" id="cogs" name="cogs" placeholder="Harga Pokok" value="<?= (isset($edit['cogs'])) ? intval($edit['cogs']) : '' ?>" data-prefix="Rp. " autocomplete="off">
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-6 mb-2">
                <label for="name" class="mb-1">Harga Jual</label>
                <input type="currency" class="form-control" id="selling_price" name="selling_price" placeholder="Harga Jual" value="<?= (isset($edit['selling_price'])) ? intval($edit['selling_price']) : '' ?>" data-prefix="Rp. " autocomplete="off">
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-12 mb-2">
                <label for="name" class="mb-1">Keterangan</label>
                <textarea name="description" id="description" class="form-control" placeholder="Keterangan"><?= @$edit['description'] ?></textarea>
                <div class="invalid-feedback"></div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-danger" type="button" data-dismiss="modal">Close</button>
        <button class="btn btn-success float-right" type="submit">Simpan</button>
    </div>
</form>