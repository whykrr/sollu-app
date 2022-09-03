<div class="modal-header">
    <h4 class="modal-title">Detail Stock</h4>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-md-6 mb-0">
            <p><strong>Kode Produk :</strong> <br> <?= $product['code'] ?></p>

            <p><strong>Nama Produk :</strong> <br> <?= $product['name'] ?></p>

            <p><strong>Kategori :</strong> <br> <?= (!empty($product['category_name'])) ? $product['category_name'] : '-' ?></p>
        </div>
        <div class="col-md-6">
            <p><strong>Stok :</strong> <br> <?= $product['stock'] . " " . $product['unit_name'] ?></p>

            <p><strong>Minimal Stok :</strong> <br> <?= $product['stock_min'] . " " . $product['unit_name'] ?></p>
            <p><strong>Harga Jual :</strong> <br> <?= formatIDR($product['selling_price']) ?></p>
        </div>
    </div>
    <!-- card history stock -->
    <div class="card">
        <div class="card-header p-2">
            <b>History Stock</b>
        </div>
        <div class="card-body p-2">
            <div class="table-responsive">
                <table class="table table-sm table-bordered" id="table-history-stock">
                    <thead>
                        <tr>
                            <th>Keterangan</th>
                            <th>Stok Masuk</th>
                            <th>Stok Keluar</th>
                            <th>Harga Pokok</th>
                            <th>Harga Jual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history_stock as $key => $value) : ?>
                            <tr>
                                <td><?= $value['description'] ?></td>
                                <td><?= $value['stock_in'] . " " . $value['unit_name'] ?></td>
                                <td><?= $value['stock_out'] . " " . $value['unit_name'] ?></td>
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