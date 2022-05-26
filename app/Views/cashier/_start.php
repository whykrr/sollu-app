<form action="<?= base_url('cashier/store_log') ?>" class="ajax modal-content" data-respond="reload">
    <div class="modal-header">
        <h4 class="modal-title">Buka Kasir</h4>
    </div>
    <div class="modal-body">
        <div class="form-group row">
            <label for="begining_balance" class="col-sm-3 col-form-label">Saldo Awal</label>
            <div class="col-sm-9">
                <input type="currency" name="begining_balance" class="form-control" id="begining_balance">
            </div>
        </div>
        <div class="form-group row">
            <label for="begining_balance" class="col-sm-3 col-form-label">Kasir</label>
            <div class="col-sm-9">
                <?= user()->name ?>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <!-- <button class="btn btn-danger" type="button" data-dismiss="modal">Close</button> -->
        <button class="btn btn-success float-right" type="submit">Simpan</button>
    </div>
</form>