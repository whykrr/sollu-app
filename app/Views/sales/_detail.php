<div class="modal-header">
    <h4 class="modal-title">Detail Penerimaan</h4>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-md-6 mb-0">
            <p><strong>Nomor Transaksi :</strong> <br> <?= $data['invoice_no'] ?></p>
            <p><strong>Tanggal :</strong> <br> <?= formatDateSimple($data['date']) ?></p>
            <p><strong>Kasir :</strong> <br> <?= $data['user_name'] ?></p>
            <p><strong>Nama Pelanggan :</strong> <br> <?= (!empty($data['customer'])) ? $data['customer'] : '-'; ?></p>
        </div>
        <div class="col-md-6">
            <p><strong>Total Penjualan :</strong> <br> <?= formatIDR($data['total']) ?></p>
            <p><strong>Diskon :</strong> <br> <?= formatIDR($data['discount']) ?></p>
            <p><strong>Grand Total :</strong> <br> <?= formatIDR($data['grand_total']) ?></p>
            <p><strong>Tipe Pembayaran :</strong> <br> <?= formatPaymentType($data['payment_type']) ?></p>
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
    <a class="btn btn-success" href="<?= base_url("sales/exporttr?invoice=$data[invoice_no]") ?>">
        <span>Export Data</span>
    </a>
    <button class=" btn btn-warning float-right ajax-del" data-source="sales" key="<?= $data['id'] ?>" type="button">Hapus Transaksi</button>
</div>