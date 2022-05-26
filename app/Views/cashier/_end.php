<form action="<?= base_url('cashier/attemp_end_cashier') ?>" class="ajax modal-content" data-respond="reload">
    <div class="modal-header">
        <h4 class="modal-title">Tutup Kasir</h4>
    </div>
    <div class="modal-body">
        <input type="hidden" name="id" value="<?= @$data['id'] ?>">
        <div class="form-group row mb-2">
            <label for="" class="col-sm-3 col-form-label">Saldo Awal</label>
            <div class="col-sm-9">
                <?= formatIDR(@$data['begining_balance']) ?>
            </div>
        </div>
        <div class="form-group row mb-2">
            <label for="ending_balance" class="col-sm-3 col-form-label">Saldo Akhir</label>
            <div class="col-sm-9">
                <input type="currency" name="ending_balance" class="form-control" id="ending_balance">
            </div>
        </div>
        <div class="form-group row mb-2">
            <label for="" class="col-sm-3 col-form-label">Jumlah Transaksi</label>
            <div class="col-sm-9">
                <?= $total_transaction ?> Transaksi
                <input type="hidden" name="total_transaction" value="<?= $total_transaction ?>">
            </div>
        </div>
        <div class="form-group row mb-2">
            <label for="" class="col-sm-3 col-form-label">Total</label>
            <div class="col-sm-9">
                <?= formatIDR($total_sales) ?>
                <input type="hidden" name="total_sales" value="<?= $total_sales ?>">
            </div>
        </div>
        <div class="form-group row mb-2">
            <label for="begining_balance" class="col-sm-3 col-form-label">Kasir</label>
            <div class="col-sm-9">
                <?= user()->name ?>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-danger" type="button" data-dismiss="modal">Close</button>
        <button class="btn btn-success float-right" type="submit">Simpan</button>
    </div>
</form>