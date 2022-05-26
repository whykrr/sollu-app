<table class="table table-sm table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Produk</th>
            <th>Harga</th>
            <th>Diskon</th>
            <th>Qty</th>
            <th>Sub Total</th>
        </tr>
    </thead>
    <tbody>
        <?php $no = 1; ?>
        <?php foreach ($product as $detail) : ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= $detail['product_code'] . ' - ' . $detail['product_name'] ?></td>
                <td><?= formatIDR($detail['price']) ?></td>
                <td><?= formatIDR($detail['discount']) ?></td>
                <td><?= $detail['qty'] ?></td>
                <td><?= formatIDR($detail['sub_total']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>