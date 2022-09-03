<div class="modal-header">
    <h4 class="modal-title">Detail Pembelian Stok</h4>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-md-6 mb-0">
            <p><strong>Invoice No :</strong> <br> <?= $data['invoice_no'] ?></p>

            <p><strong>Tanggal Pembelian :</strong> <br> <?= formatDateID($data['date']) ?></p>
        </div>
        <div class="col-md-6">
            <p><strong>Supplier :</strong> <br> <?= $data['supplier'] ?></p>
            <p><strong>Total Pembelian :</strong> <br> <?= formatIDR($data['grand_total']) ?></p>
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
                            <th>Kode Produk</th>
                            <th>Nama Produk</th>
                            <th>Qty</th>
                            <th>Harga Pokok</th>
                            <th>Harga Jual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detail as $key => $value) : ?>
                            <tr>
                                <td><?= $value['product_code'] ?></td>
                                <td><?= $value['product_name'] ?></td>
                                <td><?= $value['qty'] . " " . $value['unit_name'] ?></td>
                                <td><?= formatIDRHidden($value['cogs']) ?></td>
                                <td><?= formatIDR($value['selling_price']) ?></td>
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
</div>