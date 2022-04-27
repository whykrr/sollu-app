<form action="<?= base_url('user/save') ?>" class="ajax modal-content">
    <?php if (@$edit['id']) : ?>
        <input type="hidden" name="id" value="<?= $edit['id'] ?>">
        <input type="hidden" name="login" value="<?= $edit['username'] ?>">
    <?php endif; ?>
    <div class="modal-header">
        <h4 class="modal-title"><?= (!@$edit['id'] ? 'Tambah' : '') ?> Data Pengguna</h4>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-6 mb-2 mr-0">
                <label for="name" class="mb-1">Nama</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Nama" value="<?= @$edit['name'] ?>">
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-6 mb-2">
                <label for="name" class="mb-1">Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" value="<?= @$edit['username'] ?>">
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-12 mb-2">
                <label for="name" class="mb-1">Hak Akses</label>
                <select name="group" id="group" class="form-control">
                    <option value="">-- PILIH --</option>
                    <?php foreach ($groups as $key => $value) : ?>
                        <option value="<?= $value->name ?>" <?= (@in_array($value->name, @$edit['group']) ? 'selected' : '') ?>><?= $value->description ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"></div>
            </div>
            <?php if (@$edit['id']) : ?>
                <div class="col-md-12 mb-2 mr-0">
                    <label for="name" class="mb-1">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" autocomplete="off">
                    <small id="codeHelp" class="form-text text-muted">Masukkan password untuk merubah data.</small>
                    <div class="invalid-feedback"></div>
                </div>
            <?php else : ?>
                <div class="col-md-6 mb-2 mr-0">
                    <label for="name" class="mb-1">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" autocomplete="off">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-md-6 mb-2">
                    <label for="name" class="mb-1">Konfirmasi Password</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Konfirmasi Password" autocomplete="off">
                    <div class="invalid-feedback"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-danger" type="button" data-dismiss="modal">Close</button>
        <button class="btn btn-success float-right" type="submit">Simpan</button>
    </div>
</form>