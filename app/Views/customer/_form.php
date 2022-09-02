<form action="<?= base_url('customer/save') ?>" class="ajax modal-content">
    <?php if (@$edit['id']) : ?>
        <input type="hidden" name="id" value="<?= $edit['id'] ?>">
    <?php endif; ?>
    <div class="modal-header">
        <h4 class="modal-title"><?= (!@$edit['id'] ? 'Tambah' : '') ?> Data Pelanggan</h4>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12 mb-2">
                <label for="name" class="mb-1">Nama</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Nama" value="<?= @$edit['name'] ?>">
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-12 mb-2">
                <label for="name" class="mb-1">Alamat</label>
                <textarea class="form-control" id="address" name="address" placeholder="Alamat"><?= @$edit['address'] ?></textarea>
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-12 mb-2">
                <label for="phone" class="mb-1">Telepon</label>
                <input type="text" class="form-control" id="phone" name="phone" placeholder="Telepon" value="<?= @$edit['phone'] ?>">
                <div class="invalid-feedback"></div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-danger" type="button" data-dismiss="modal">Close</button>
        <button class="btn btn-success float-right" type="submit">Simpan</button>
    </div>
</form>