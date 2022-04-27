<?= $this->extend('layout/general_layout'); ?>

<?= $this->section('breadcrumb'); ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb my-0 ms-2">
        <li class="breadcrumb-item"><span>Kasir</span></li>
    </ol>
</nav>
<?= $this->endSection('breadcrumb'); ?>

<?= $this->section('main'); ?>
<div class="container-fluid">
    <div class="fade-in">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header p-2">
                        <h5 class="card-title mb-0"><b>Transaksi</b></h5>
                    </div>
                    <?php
                    $tr_code = "TR" . rand(10000, 99999) . '.' . date('Y') . '.' . date('m') . '-' . date('his');
                    $tr_date = date('Y-m-d');
                    ?>
                    <div class="card-body p-2">
                        <input type="hidden" id="transaction_code" value="<?= $tr_code ?>">
                        <input type="hidden" id="transaction_date" value="<?= $tr_date ?>">
                        <input type="hidden" id="user_id" value="<?= user_id() ?>">
                        <div class="row">
                            <div class="col-md-4">
                                <p class="mb-1"><strong>Nomor Transaksi</strong></p>
                                <p class="mb-1"><strong>Tanggal</strong></p>
                                <p class="mb-1"><strong>Kasir</strong></p>
                            </div>
                            <div class="col-md-8">
                                <p class="mb-1">: <?= $tr_code ?></p>
                                <p class="mb-1">: <?= formatDateID($tr_date) ?></p>
                                <p class="mb-1">: <?= user()->name ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header p-2">
                        <h5 class="card-title mb-0">Detail Barang</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="row">
                            <div class="col-md-4 pr-0">
                                <div class="form-group mb-1">
                                    <label for="product-display">Kode - Nama</label>
                                    <input type="text" class="form-control form-item" id="product-display" autocomplete="off" placeholder="Cari kode atau nama ..."></input>
                                    <input type="hidden" id="product_id">
                                </div>
                            </div>
                            <div class="col-md-4 pr-0">
                                <div class="form-group mb-1">
                                    <label for="price">Harga</label>
                                    <input type="text" class="form-control form-item" id="price-display" disabled>
                                    <input type="hidden" class="form-item" id="product_price">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-1">
                                    <label for="qty">Qty</label>
                                    <input type="number" class="form-control form-item" id="qty" placeholder="Qty">
                                </div>
                            </div>
                            <div class="col-md-2 pl-0 align-self-end">
                                <div class="form-group mb-1 ">
                                    <button type="button" id="btn-item-add" class="btn btn-block btn-success">
                                        <i class="c-icon cil-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="table-items">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Harga</th>
                                        <th>Qty</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 pl-0">
                <div class="card text-white bg-info mb-3">
                    <div class="card-header p-2">
                        <h5 class="card-title mb-0"><b>Grand Total</b></h5>
                    </div>
                    <div class="card-body px-2">
                        <h1 class="float-right"><strong id="grand-total">Rp. 0</strong></h1>
                        <input type="hidden" id="grand-total-input" value="0">
                    </div>
                </div>
                <div class="card">
                    <div class="card-body px-2">
                        <div class="form-group row mb-1">
                            <label for="" class="col-md-5 col-form-label font-weight-bold">Total</label>
                            <div class="col-md-7">
                                <h3 class="float-right" id="total">Rp. 0</h3>
                                <input type="hidden" id="total-input" value="0">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="discount" class="col-md-5 col-form-label font-weight-bold">Diskon</label>
                            <div class="col-md-7">
                                <input type="currency" id="discount" class="form-control" data-prefix="Rp. ">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="customer" class="col-md-5 col-form-label font-weight-bold">Nama Pelanggan</label>
                            <div class="col-md-7">
                                <input type="text" class="form-control" id="customer">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="payment_type" class="col-md-5 col-form-label font-weight-bold">Tipe Pembayaran</label>
                            <div class="col-md-7">
                                <select id="payment_type" class="form-control">
                                    <option value="0">Cash</option>
                                    <option value="1">Kredit</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row type-credit d-none">
                            <label for="duedate" class="col-md-5 col-form-label font-weight-bold">Jatuh Tempo</label>
                            <div class="col-md-7">
                                <input type="date" class="form-control" id="duedate">
                            </div>
                        </div>
                        <div class="form-group row type-cash">
                            <label for="cash" class="col-md-5 col-form-label font-weight-bold">Bayar</label>
                            <div class="col-md-7">
                                <input type="currency" id="cash" class="form-control" data-prefix="Rp. ">
                            </div>
                        </div>
                        <div class="form-group row mb-1 type-cash">
                            <label for="" class="col-md-5 col-form-label font-weight-bold">Kembalian</label>
                            <div class="col-md-7">
                                <h4 class="float-right" id="cash-return">Rp. 0</h4>
                                <input type="hidden" id="cash-return-input" value="0">
                            </div>
                        </div>
                        <button class="btn btn-lg btn-block btn-success" id="save-transaction"><strong>Simpan</strong></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection('main'); ?>
<?= $this->section('js'); ?>
<script>
    items = [];
    $('#product-display').typeahead({
        source: function(query, result) {
            $.ajax({
                url: "<?= base_url('masterdata/product/autocomplete') ?>",
                data: 'q=' + query,
                dataType: "json",
                type: "GET",
                success: function(data) {
                    result($.map(data, function(item) {
                        return item;
                    }));
                }
            });
        },
        autoSelect: true,
        afterSelect: function(item) {
            $('#product_id').val(item.id);
            $('#price-display').val(formatRupiah(item.selling_price.toString(), true));
            $('#product_price').val(parseInt(item.selling_price));
            $('#qty').focus();
            $('#qty').val(1);
        }
    });

    // acction #qty on enter
    $('#qty').keypress(function(e) {
        if (e.which == 13) {
            $('#btn-item-add').click();
        }
    });

    // action #btn-item-add
    $('#btn-item-add').click(function(e) {
        e.preventDefault

        // check product id if exist on items
        if ($('#product_id').val() == '') {
            swal('Oops', 'Kode atau nama barang tidak boleh kosong', 'error');
            $('#product-display').focus();
            return false;
        }

        // replace items if same roduct id
        var item_exist = false;
        $.each(items, function(index, value) {
            if (value.product_id == $('#product_id').val()) {
                item_exist = true;
                items[index].qty = parseInt(items[index].qty) + parseInt($('#qty').val());
                items[index].subtotal = parseInt(items[index].qty) * parseInt(items[index].price);
            }
        });

        // append to items
        if (!item_exist) {
            items.push({
                product_id: $('#product_id').val(),
                product_name: $('#product-display').val(),
                price: $('#product_price').val(),
                qty: $('#qty').val(),
                subtotal: $('#qty').val() * $('#product_price').val()
            });
        }

        console.log(items);
        appendItemsToTable();
        countTotal();

        // remove value .form-item
        $('.form-item').val('');
        $('#product-display').focus();
    })

    // action keyup on discount
    $('#discount').keyup(function(e) {
        countGrandTotal();
    });

    //function append items to table
    function appendItemsToTable() {
        var html = '';
        $.each(items, function(index, value) {
            html += '<tr>';
            html += '<td>' + value.product_name + '</td>';
            html += '<td>' + formatRupiah(value.price.toString(), true) + '</td>';
            html += '<td>' + value.qty + '</td>';
            html += '<td>' + formatRupiah(value.subtotal.toString(), true) + '</td>';
            // html += '<td><button class="btn btn-sm btn-danger btn-delete-item" data-id="' + value.product_id + '"><i class="fa fa-trash"></i></button></td>';
            html += '</tr>';
        });
        $('#table-items tbody').html(html);
    }

    // function count total
    function countTotal() {
        var total = 0;
        $.each(items, function(index, value) {
            total += parseInt(value.subtotal);
        });
        $('#total').html(formatRupiah(total.toString(), true));
        $('#total-input').val(total);
        countGrandTotal();
    }

    // function count grand total
    function countGrandTotal() {
        // check discount if empty
        if ($('#discount').val() == '') {
            discount = 0;
        } else {
            discount = $('#discount').val();
        }

        var grand_total = parseInt($('#total-input').val()) - parseInt(discount);
        $('#grand-total').html(formatRupiah(grand_total.toString(), true));
        $('#grand-total-input').val(grand_total);
    }

    // show #info-duedate
    $('#payment_type').change(function(e) {
        if ($('#payment_type').val() == '1') {
            $('.type-credit').removeClass('d-none');
            $('.type-cash').addClass('d-none');
        } else {
            $('.type-credit').addClass('d-none');
            $('.type-cash').removeClass('d-none');
        }
    });

    // count cash return
    $('#cash').keyup(function(e) {
        // check if cash is empty
        cash = 0
        if ($('#cash').val() != '') {
            cash = $('#cash').val();
        }

        var grand_total = $('#grand-total-input').val();

        // check if cash is less than grand total
        if (parseInt(cash) >= parseInt(grand_total)) {
            $('#cash-return').html(formatRupiah((parseInt(cash) - parseInt(grand_total)).toString(), true));
            $('#cash-return-input').val(parseInt(cash) - parseInt(grand_total));
        } else {
            $('#cash-return').html(formatRupiah('0', true));
            $('#cash-return-input').val(0);
        }
    });

    // action #save-transaction
    $('#save-transaction').click(function(e) {
        e.preventDefault();

        // check if items is empty
        if (items.length == 0) {
            swal('Oops', 'Tidak ada barang yang dibeli', 'error');
            return false;
        }

        // check if cash is empty
        if (($('#cash').val() == '') && ($('#payment_type').val() == '0')) {
            swal('Oops', 'Jumlah uang harus diisi', 'error');
            $('#cash').focus();
            return false;
        }

        // check if cash is less than grand total
        if ((parseInt($('#cash').val()) < parseInt($('#grand-total-input').val())) && ($('#payment_type').val() == '0')) {
            swal('Oops', 'Jumlah uang tidak cukup', 'error');
            $('#cash').focus();
            return false;
        }

        // check if payment type is empty
        if ($('#payment_type').val() == '') {
            swal('Oops', 'Pilih metode pembayaran', 'error');
            $('#payment_type').focus();
            return false;
        }

        // check if customer name is empty
        if ($('#payment_type').val() == '1' && $('#customer').val() == '') {
            swal('Oops', 'Nama pelanggan harus diisi', 'error');
            $('#customer').focus();
            return false;
        }

        // check if duedate is empty
        if ($('#payment_type').val() == '1' && $('#duedate').val() == '') {
            swal('Oops', 'Tanggal jatuh tempo harus diisi', 'error');
            $('#duedate').focus();
            return false;
        }

        // check if duedate is less than today
        if ($('#payment_type').val() == '1' && $('#duedate').val() < getToday()) {
            swal('Oops', 'Tanggal jatuh tempo tidak boleh kurang dari hari ini', 'error');
            $('#duedate').focus();
            return false;
        }

        // check payment type and set duedate
        if ($('#payment_type').val() == '1') {
            duedate = $('#duedate').val();
        } else {
            duedate = null;
        }

        // creeate json data
        var data = {
            'invoice_no': $('#transaction_code').val(),
            'date': $('#transaction_date').val(),
            'user_id': $('#user_id').val(),
            'customer': $('#customer').val(),
            'items': items,
            'total': $('#total-input').val(),
            'discount': $('#discount').val(),
            'grand_total': $('#grand-total-input').val(),
            'payment_type': $('#payment_type').val(),
            'duedate': duedate,
            'cash': $('#cash').val(),
            'cash_return': $('#cash-return-input').val(),
        };

        // send ajax
        $.ajax({
            url: '<?= base_url('cashier/save') ?>',
            type: 'POST',
            dataType: 'json',
            data: data,
            beforeSend: function() {
                $('#save-transaction').html('<i class="fa fa-spin fa-spinner"></i>');
            },
            success: function(response) {
                // reload page
                window.location.reload();
            },
            error: function(xhr, status, error) {
                if (xhr.status == 404) {
                    swal('Oops', 'not found', 'error');
                } else {
                    console.log(xhr);
                    swal('Oops', xhr.responseJSON.message, 'error');
                }
            },
            complete: function() {
                $('#save-transaction').html('Simpan');
            }
        });
    });
</script>
<?= $this->endSection('js'); ?>