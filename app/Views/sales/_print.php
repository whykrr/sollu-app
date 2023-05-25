<!DOCTYPE html>
<html lang="en">

<head>
    <base href="<?= base_url(); ?>/">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/style.css" rel="stylesheet">
    <title>Print Piutang</title>
</head>

<style>
    @page {
        size: A4;
    }
</style>

<body onload="window.print()" onafterprint="history.back();">
    <center>
        <h1>PENJUALAN</h1>
    </center>
    <div class="row mx-0">
        <div style="width: 50%;">
            <p><strong>Nomor Transaksi :</strong> <br> <?= $data['invoice_no'] ?></p>
            <p><strong>Tanggal :</strong> <br> <?= formatDateSimple($data['date']) ?></p>
            <p><strong>Kasir :</strong> <br> <?= $data['user_name'] ?></p>
            <p><strong>Nama Pelanggan :</strong> <br> <?= (!empty($data['customer'])) ? $data['customer'] : '-'; ?></p>
        </div>
        <div style="width: 50%;">
            <p><strong>Total Penjualan :</strong> <br> <?= formatIDR($data['total']) ?></p>
            <p><strong>Diskon :</strong> <br> <?= formatIDR($data['discount']) ?></p>
            <p><strong>Grand Total :</strong> <br> <?= formatIDR($data['grand_total']) ?></p>
            <p><strong>Tipe Pembayaran :</strong> <br> <?= formatPaymentType($data['payment_type']) ?></p>
        </div>
    </div>
    <!-- card history payment -->
    <div class="card">
        <div class="card-header p-2">
            <b>Daftar Barang</b>
        </div>
        <div class="card-body p-2">
            <div class="table-responsive">
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
            </div>
        </div>
    </div>
</body>

</html>