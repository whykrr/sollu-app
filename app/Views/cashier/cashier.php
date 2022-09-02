<?= $this->extend('layout/general_layout'); ?>

<?= $this->section('main'); ?>
<div class="container-fluid">
    <div class="fade-in">
        <div class="row">
            <div class="col-md-8 pl-0">
                <div class="card mb-3">
                    <?php
                    $tr_code = "TR" . rand(10000, 99999) . '.' . date('Y') . '.' . date('m') . '-' . date('his');
                    $tr_date = date('Y-m-d');
                    ?>
                    <div class="card-body p-2">
                        <input type="hidden" id="transaction_code" value="<?= $tr_code ?>">
                        <input type="hidden" id="transaction_date" value="<?= $tr_date ?>">
                        <input type="hidden" id="user_id" value="<?= user_id() ?>">
                        <input type="hidden" id="cashier_log_id" value="<?= $cashier_log_id ?>">
                        <input type="hidden" id="with_log" value="<?= $with_log ?>">
                        <div class="row">
                            <div class="col-md-4">
                                <p class="mb-1"><strong>Nomor Transaksi</strong></p>
                                <p class="mb-1"><?= $tr_code ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1"><strong>Tanggal</strong></p>
                                <p class="mb-1"><?= formatDateID($tr_date) ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1"><strong>Kasir</strong></p>
                                <p class="mb-1"><?= user()->name ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body p-2">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-1">
                                    <label for="barcode">Barcode</label>
                                    <input type="text" class="form-control form-item" id="barcode" autocomplete="off" placeholder="Scan barcode ..."></input>
                                </div>
                            </div>
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
                                    <input type="hidden" class="form-item" id="cogs">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-1">
                                    <label for="qty">Qty</label>
                                    <input type="number" class="form-control form-item" id="qty" placeholder="Qty">
                                    <input type="hidden" id="stock">
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
                            <table class="table table-sm table-bordered table-align-middle" id="table-items">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Harga</th>
                                        <th>Diskon Satuan</th>
                                        <th>Qty</th>
                                        <th>Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 px-0">
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
                                <input type="currency" id="discount" class="form-control" autocomplete="off" data-prefix="Rp. ">
                            </div>
                        </div>
                        <?php if (verifyPos('complex')) : ?>
                            <div class="form-group row">
                                <label for="customer" class="col-md-5 col-form-label font-weight-bold">Nama Pelanggan</label>
                                <div class="col-md-7">
                                    <input type="hidden" class="form-control" id="customer_id">
                                    <input type="text" class="form-control" id="customer" autocomplete="off">
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
                                    <input type="date" class="form-control" id="duedate" autocomplete="off">
                                </div>
                            </div>
                        <?php else : ?>
                            <input type="hidden" id="customer" value="">
                            <input type="hidden" id="payment_type" value="0">
                        <?php endif; ?>
                        <div class="form-group row type-cash">
                            <label for="cash" class="col-md-5 col-form-label font-weight-bold">Bayar</label>
                            <div class="col-md-7">
                                <input type="currency" id="cash" class="form-control" autocomplete="off" data-prefix="Rp. ">
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

    // focus #barcode on load
    $('#barcode').focus();

    $(document).keyup(function(e) {
        if (e.which == 35) {
            e.preventDefault();
            $('#cash').focus();
        }
        if (e.which == 36) {
            e.preventDefault();
            $('#barcode').focus();
        }
    });

    $('#barcode').keypress(function(e) {
        if (e.which == 13) {
            e.preventDefault();
            $.ajax({
                url: "<?= base_url('masterdata/product/barcode') ?>",
                data: 'scan=' + $(this).val(),
                dataType: "json",
                type: "GET",
                success: function(item) {
                    if (item.stock < 1) {
                        notEnoughStock();
                        clearFormCashier();
                    } else {
                        $('#product_id').val(item.id);
                        $('#product-display').val(item.name);
                        $('#product_price').val(parseInt(item.selling_price));
                        $('#cogs').val(parseInt(item.cogs));
                        if ($('#qty').val() == '') {
                            $('#qty').val(1);
                        }
                        $('#stock').val(parseInt(item.stock));

                        // click add btn
                        $('#btn-item-add').click();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    swal("Oops!", "kode barcode tidak ada", "error");
                    clearFormCashier();
                }
            });
        }
        if (e.which == 35) {
            e.preventDefault();
            $('#cash').focus();
        }
    });
    $('#product-display').typeahead({
        highlight: true,
        items: 15,
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
            $('#cogs').val(parseInt(item.cogs));
            $('#stock').val(parseInt(item.stock));
            $('#qty').focus();
            $('#qty').val("");
        }
    });

    $(document).find('#customer').typeahead({
        highlight: true,
        items: 15,
        source: function(query, result) {
            $.ajax({
                url: "<?= base_url('customer/autocomplete') ?>",
                data: 'q=' + query,
                dataType: "json",
                type: "GET",
                success: function(data) {
                    $('#customer_id').val("");
                    result($.map(data, function(item) {
                        return item;
                    }));
                }
            });
        },
        autoSelect: true,
        afterSelect: function(item) {
            $('#customer_id').val(item.id);
            $('#customer').val(item.name);
        }
    });

    // acction #qty on enter
    $('#qty').keypress(function(e) {
        if (e.which == 13) {
            // get value if empty
            if ($(this).val() == "") {
                $(this).val(1);
            }
            $('#btn-item-add').click();
        }
    });

    // action #btn-item-add
    $('#btn-item-add').click(function(e) {
        e.preventDefault

        if ($("#qty").val() == "") {
            $("#qty").val(1);
        }

        // check product id if exist on items
        if ($('#product_id').val() == '') {
            swal('Oops', 'Kode atau nama barang tidak boleh kosong', 'error');
            $('#product-display').focus();
            return false;
        }

        // check stock less than qty
        if (parseInt($('#stock').val()) < parseInt($('#qty').val())) {
            notEnoughStock();
            return false;
        }

        // replace items if same roduct id
        var item_exist = false;
        $.each(items, function(index, value) {
            if (value.product_id == $('#product_id').val()) {
                item_exist = true;
                if (parseInt($('#stock').val()) < parseInt(items[index].qty) + parseInt($('#qty').val())) {
                    notEnoughStock();
                    return false;
                }
                items[index].qty = parseInt(items[index].qty) + parseInt($('#qty').val());
                items[index].subtotal = parseInt(items[index].qty) * parseInt(items[index].price - items[index].discount);

                // stop loop
                return false;
            }
        });

        // append to items
        if (!item_exist) {
            items.push({
                product_id: $('#product_id').val(),
                product_name: $('#product-display').val(),
                cogs: $('#cogs').val(),
                price: $('#product_price').val(),
                discount: '0',
                qty: $('#qty').val(),
                subtotal: $('#qty').val() * $('#product_price').val()
            });
        }

        console.log(items);
        appendItemsToTable();
        countTotal();

        clearFormCashier();
    })

    function clearFormCashier() {
        $('.form-item').val('');
        $('#barcode').focus();
    }

    function notEnoughStock() {
        swal('Oops', 'Stok tidak cukup', 'error');
        $('#product-display').focus();
    }

    //action .btn-delete-item
    $(document).on('click', '.btn-delete-item', function(e) {
        e.preventDefault();
        var index = $(this).data('index');
        items.splice(index, 1);
        appendItemsToTable();
        countTotal();
    })

    // action .discount-item
    $(document).on('change', '.discount-item', function(e) {
        e.preventDefault();
        var index = $(this).data('index');
        console.log(items[index]);
        items[index].discount = $(this).val();
        items[index].subtotal = (items[index].price - items[index].discount) * items[index].qty;
        appendItemsToTable();
        countTotal();
    })

    // action keyup on discount
    $('#discount').keyup(function(e) {
        countGrandTotal();
    });

    //function append items to table
    function appendItemsToTable() {
        var html = '';
        var item_reverse = items
        item_reverse.reverse()

        $.each(item_reverse, function(index, value) {
            html += '<tr>';
            html += '<td>' + value.product_name + '</td>';
            html += '<td>' + formatRupiah(value.price.toString(), true) + '</td>';
            html += '<td><input type="currency" class="form-control discount-item" data-index="' + index + '" value="' + value.discount + '"></td>';
            html += '<td>' + value.qty + '</td>';
            html += '<td>' + formatRupiah(value.subtotal.toString(), true) + '</td>';
            html += '<td><button class="btn btn-sm btn-danger btn-delete-item" data-index="' + index + '"><i class="c-icon cil-trash"></i></button></td>';
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

    $('#cash').keypress(function(e) {
        if (e.which == 13) {
            e.preventDefault();
            $('#save-transaction').click();
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
            'cashier_log_id': $('#cashier_log_id').val(),
            'date': $('#transaction_date').val(),
            'user_id': $('#user_id').val(),
            'customer_id': $('#customer_id').val(),
            'customer': $('#customer').val(),
            'items': items,
            'total': $('#total-input').val(),
            'discount': $('#discount').val(),
            'grand_total': $('#grand-total-input').val(),
            'payment_type': $('#payment_type').val(),
            'duedate': duedate,
            'pay': $('#cash').val(),
            'return': $('#cash-return-input').val(),
        };

        $('#save-transaction').html('Loading...');
        $('#save-transaction').attr('disabled', true);

        // send ajax
        $.ajax({
            url: '<?= base_url('cashier/save') ?>',
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function(response) {
                // swall alert
                swal({
                    title: 'Berhasil',
                    text: 'Transaksi berhasil disimpan',
                    icon: "success",
                    button: "OK",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                }).then(function() {
                    // reload page
                    window.location.reload();
                });
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
                $('#save-transaction').attr('disabled', false);

            }
        });
    });
</script>
<?= $this->endSection('js'); ?>