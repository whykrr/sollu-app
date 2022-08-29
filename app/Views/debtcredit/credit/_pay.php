<form action="<?= base_url('debtcredit/credit/attemppay') ?>" class="ajax modal-content">
    <input type="hidden" name="account_receivable_id" value="<?= $data['id'] ?>">
    <div class="modal-header">
        <h4 class="modal-title">Pembayaran Piutang</h4>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-6 mb-0">
                <p><strong>Invoice No :</strong> <br> <?= $data['invoice_no'] ?></p>

                <p><strong>Tanggal Pembelian :</strong> <br> <?= formatDateID($data['date']) ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Total Pembelian :</strong> <br> <?= formatIDR($data['amount']) ?></p>
                <input type="hidden" name="total_credit" value="<?= ($data['amount']) ?>">
                <input type="hidden" name="pay_amount" value="<?= ($data['pay_amount']) ?>">
            </div>
            <div class="col-md-6">
                <p><strong>Sisa Piutang :</strong> <br> <?= formatIDR($data['amount'] - $data['pay_amount']) ?></p>
                <input type="hidden" name="remaining_credit" value="<?= ($data['amount'] - $data['pay_amount']) ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-2 pr-0">
                <label for="date" class="mb-1">Tanggal Pembayaran</label>
                <input type="date" class="form-control" id="date" name="date" value="<?= date('Y-m-d') ?>" autocomplete="off">
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-md-6 mb-2">
                <label for="amount" class="mb-1">Total Pembayaran</label>
                <input type="currency" class="form-control" id="amount" name="amount" placeholder="Total pembayaran" value="" data-prefix="Rp. " autocomplete="off">
                <div class="invalid-feedback"></div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-danger" type="button" data-dismiss="modal">Close</button>
        <button class="btn btn-success float-right" type="button" id="btn-paid">Lunasi</button>
        <button class="btn btn-success float-right" type="submit">Simpan</button>
    </div>
</form>
<form class="ajax" id="paid" action="<?= base_url('debtcredit/credit/attemppay') ?>">
    <input type="hidden" name="total_credit" value="<?= ($data['amount']) ?>">
    <input type="hidden" name="account_receivable_id" value="<?= $data['id'] ?>">
    <input type="hidden" name="remaining_credit" value="<?= ($data['amount'] - $data['pay_amount']) ?>">
    <input type="hidden" name="pay_amount" value="<?= $data['pay_amount'] ?>">
    <input type="hidden" name="date" value="<?= date('Y-m-d') ?>">
    <input type="hidden" name="amount" value="<?= ($data['amount'] - $data['pay_amount']) ?>">
</form>
<script>
    // when click #btn-paid submit #paid
    $('#btn-paid').click(function() {
        // disabled
        $('#btn-paid').attr('disabled', true);
        $('#paid').submit();
    });
</script>