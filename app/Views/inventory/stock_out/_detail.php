<div class="modal-header">
    <h4 class="modal-title">Detail Penerimaan</h4>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-md-6 mb-0">
            <p><strong>Nomor Transaksi :</strong> <br> <?= $data['invoice_no'] ?></p>
            <p><strong>Tanggal :</strong> <br> <?= formatDateSimple($data['date']) ?></p>
            <p><strong>User :</strong> <br> <?= $data['user_name'] ?></p>
            <p><strong>Catatan :</strong> <br> <?= (!empty($data['note'])) ? $data['note'] : '-'; ?></p>
        </div>
        <div class="col-md-6">
            <p><strong>Total :</strong> <br> <?= formatIDR($data['total']) ?></p>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-md-12">
            <h3>Detail Stok Keluar</h3>
            <div class="table-responsive">
                <?= $this->include('sales/_product_sales'); ?>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button class="btn btn-danger" type="button" data-dismiss="modal">Close</button>
    <button class="btn btn-warning float-right ajax-del" data-source="sales" key="<?= $data['id'] ?>" type="button">Hapus Stok Keluar</button>
</div>