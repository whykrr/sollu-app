<div class="modal-header">
    <h4 class="modal-title">Detail Piutang</h4>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-md-6 mb-0">
            <p><strong>Invoice No :</strong> <br> <?= $data['invoice_no'] ?></p>

            <p><strong>Tanggal Pembelian :</strong> <br> <?= formatDateID($data['date']) ?></p>
        </div>
        <div class="col-md-6">
            <p><strong>Total Pembelian :</strong> <br> <?= formatIDR($data['amount']) ?></p>

            <p><strong>Pelanggan :</strong> <br> <?= $data['customer'] ?></p>
        </div>
        <div class="col-md-6">
            <p><strong>Sisa Piutang :</strong> <br> <?= formatIDR($data['amount'] - $data['pay_amount']) ?></p>
        </div>
    </div>
    <!-- card history payment -->
    <div class="card">
        <div class="card-header p-2">
            <b>Riwayat pembayaran</b>
        </div>
        <div class="card-body p-2">
            <div class="table-responsive">
                <table class="table table-sm table-bordered" id="table-history-stock">
                    <thead>
                        <tr>
                            <th>Tanggal Pembayaran</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // if empty show 'record not found'
                        if (count($detail) == 0) {
                            echo '<tr><td colspan="4" class="text-center">Record not found</td></tr>';
                        } else {
                            foreach ($detail as $row) {
                        ?>
                                <tr>
                                    <td><?= formatDateID($row['date']) ?></td>
                                    <td><?= formatIDR($row['amount']) ?></td>
                                </tr>
                        <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- card history stock -->
    <div class="card">
        <div class="card-header p-2">
            <b>List Pembelian</b>
        </div>
        <div class="card-body p-2">
            <div class="table-responsive">
                <table class="table table-sm table-bordered" id="table-history-stock">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $key => $value) : ?>
                            <tr>
                                <td><?= $value['product_name'] . ' - ' . $value['product_code'] ?></td>
                                <td><?= formatIDR($value['price']) ?></td>
                                <td><?= $value['qty'] . " " . $value['unit_name'] ?></td>
                                <td><?= formatIDR($value['sub_total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button class="btn btn-danger" type="button" data-dismiss="modal">Close</button>
    <a href="<?= base_url('debtcredit/credit/print/' . $data['id']) ?>" class="btn btn-primary">Print</a>
</div>