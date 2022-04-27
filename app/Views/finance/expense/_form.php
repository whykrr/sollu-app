<form action="<?= base_url('finance/expense/save') ?>" class="ajax modal-content">
    <?php if (@$edit['id']) : ?>
        <input type="hidden" name="id" value="<?= $edit['id'] ?>">
    <?php endif; ?>
    <div class="modal-header">
        <h4 class="modal-title"><?= (!@$edit['id'] ? 'Tambah' : '') ?> Data Pengeluaran</h4>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12 mb-2">
                <label for="account_id" class="mb-1">Tipe Pengeluaran</label>
                <select name="account_id" id="account_id" class="form-control">
                    <option value="">-- PILIH --</option>
                    <option <?= (@$edit['account_id'] == '9-1') ? 'selected' : null ?> value="9-1">Beban Operasional</option>
                    <option <?= (@$edit['account_id'] == '9-2') ? 'selected' : null ?> value="9-2">Beban Lainnya</option>
                </select>
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-6 mb-2 mr-0">
                <label for="amount" class="mb-1">Total Pengeluaran</label>
                <input type="currency" class="form-control" id="amount" name="amount" placeholder="Total Pengeluaran" value="<?= (isset($edit['amount'])) ? intval($edit['amount']) : '' ?>" data-prefix="Rp. " autocomplete="off">
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-6 mb-2">
                <label for="description" class="mb-1">Keterangan</label>
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