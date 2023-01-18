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
        <h1>PIUTANG</h1>
    </center>
    <div class="row mx-0">
        <div style="width: 50%;">
            <p><strong>Invoice No :</strong> <br> <?= $data['invoice_no'] ?></p>
            <p><strong>Tanggal Pembelian :</strong> <br> <?= formatDateID($data['date']) ?></p>
            <p><strong>Sisa Piutang :</strong> <br> <?= formatIDR($data['amount'] - $data['pay_amount']) ?></p>
        </div>
        <div style="width: 50%;">
            <p><strong>Total Pembelian :</strong> <br> <?= formatIDR($data['amount']) ?></p>

            <p><strong>Pelanggan :</strong> <br> <?= $data['customer'] ?></p>
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
</body>

</html>