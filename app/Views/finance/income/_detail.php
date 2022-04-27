<div class="modal-header">
    <h4 class="modal-title">Detail Penerimaan</h4>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-md-6 mb-0">
            <p><strong>Nama Akun :</strong> <br> <?= $data['account_name'] ?></p>

            <p><strong>Total :</strong> <br> <?= formatIDR($data['amount']) ?></p>
        </div>
        <div class="col-md-6">
            <p><strong>Tanggal :</strong> <br> <?= formatDateID($data['created_at']) ?></p>

            <p><strong>Keterangan :</strong> <br> <?= $data['description'] ?></p>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-md-12">
            <h3>Detail Pembelian</h3>
            <div class="table-responsive">
                <?= $this->include('sales/_product_sales'); ?>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button class="btn btn-danger" type="button" data-dismiss="modal">Close</button>
</div>