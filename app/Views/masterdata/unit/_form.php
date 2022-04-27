<form action="<?= base_url('masterdata/unit/save') ?>" class="ajax modal-content">
    <?php if (@$edit['id']) : ?>
        <input type="hidden" name="id" value="<?= $edit['id'] ?>">
    <?php endif; ?>
    <div class="modal-header">
        <h4 class="modal-title"><?= (!@$edit['id'] ? 'Tambah' : '') ?> Data Satuan</h4>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12 mb-2">
                <label for="name" class="mb-1">Nama</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Nama" value="<?= @$edit['name'] ?>">
                <div class="invalid-feedback"></div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-danger" type="button" data-dismiss="modal">Close</button>
        <button class="btn btn-success float-right" type="submit">Simpan</button>
    </div>
</form>