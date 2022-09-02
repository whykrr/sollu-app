<?= $this->extend('layout/general_layout'); ?>

<?= $this->section('breadcrumb'); ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb my-0 ms-2">
        <li class="breadcrumb-item"><span>Inventory</span></li>
        <li class="breadcrumb-item"><span>Stok Keluar</span></li>
        <li class="breadcrumb-item active"><span>Input Stok Keluar</span></li>
    </ol>
</nav>
<?= $this->endSection('breadcrumb'); ?>

<?= $this->section('main'); ?>
<div class="container-fluid">
    <div class="fade-in">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Form Stok Keluar</h5>
            </div>
            <?php
            $code = 'OUT-' . rand(101, 999) . date('his') . '-' . date('dmy');
            ?>
            <div class="card-body">
                <form class="row" id="stock-out">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code">Kode</label>
                            <input type="text" class="form-control" id="code" value="<?= $code ?>">
                        </div>
                        <div class="form-group">
                            <label for="note">Catatan</label>
                            <textarea id="note" class="form-control"></textarea>
                            <div class="invalid-feedback"></div>
                            <div class="text-muted">Contoh (Diambil Toko Merakurak)</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="date">Tanggal</label>
                            <input type="date" id="action_date" class="form-control" value="<?= date('Y-m-d') ?>">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="col-md-3 pr-0">
                        <!-- card detail  -->
                        <div class="card bg-facebook text-white">
                            <div class="card-body p-2">
                                <div class="form-group mb-1">
                                    <label for="product-display">Kode - Nama</label>
                                    <input type="text" class="form-control form-item" id="product-display" autocomplete="off" placeholder="Cari kode atau nama ..."></input>
                                    <input type="hidden" id="product_id">
                                </div>
                                <div class="form-group mb-1">
                                    <label for="qty">Qty</label>
                                    <input type="number" class="form-control form-item" id="qty" placeholder="Qty">
                                </div>
                                <div class="form-group mb-1 ">
                                    <input type="hidden" class="form-control form-item" id="cogs">
                                    <input type="hidden" class="form-control form-item" id="selling_price">
                                    <input type="hidden" id="unit">

                                    <button type="button" id="btn-item-add" class="btn btn-block btn-success">
                                        <i class="c-icon cil-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="card">
                            <div class="card-header p-2">
                                <h5 class="card-title mb-0"><strong>List Barang</strong></h5>
                            </div>
                            <div class="card-body p-1">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" id="table-items">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Produk</th>
                                                <th>Qty</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 pr-0">
                        <a href="<?= base_url('inventory/stock_out') ?>" class="btn btn-danger btn-block">
                            <i class="c-icon cil-backspace"></i>
                            <span>Batal</span>
                        </a>
                    </div>
                    <div class="col-md-10">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="c-icon cil-save"></i>
                            <span>Simpan</span>
                        </button>
                    </div>
                    <?php
                    // get duedate +1 month
                    $duedate = date('Y-m-d', strtotime('+1 month'));
                    ?>
                    <input type="hidden" id="due_date" value="<?= $duedate ?>">
                    <input type="hidden" id="total">
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection('main'); ?>
<?= $this->section('js'); ?>
<script>
    items = [];

    //focused on #product-input when page loaded
    $(document).ready(function() {
        $('#product-display').focus();
    });

    $('#product-display').typeahead({
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
            $('#cogs').val(parseInt(item.cogs));
            $('#selling_price').val(parseInt(item.cogs));
            $('#qty').focus();
            $('#qty').val('');
            $('#unit').val(item.unit);
        }
    });

    // disabled enter #product-input if empty
    $('#product-display').keypress(function(e) {
        if (e.which == 13) {
            if ($('#product-display').val() == '') {
                e.preventDefault();
            }
        }
    });

    // action on enter #qty
    $('#qty').keypress(function(e) {
        if (e.which == 13) {
            e.preventDefault();
            $('#btn-item-add').click();
        }
    });

    // action on click #btn-item-add
    $('#btn-item-add').click(function() {
        if ($('#product_id').val() == '') {
            alert('Produk harus diisi');
            $('#product-display').focus();
            return false;
        }

        if ($('#qty').val() == '') {
            $('#qty').val(1)
        }

        // replace items if same product id
        var item_exist = false;
        $.each(items, function(index, value) {
            if (value.product_id == $('#product_id').val()) {
                item_exist = true;
                items[index].cogs = parseInt($('#cogs').val());
                items[index].price = parseInt($('#selling_price').val());
                items[index].qty = parseInt(items[index].qty) + parseInt($('#qty').val());
                items[index].subtotal = parseInt(items[index].qty) * parseInt(items[index].cogs);
            }
        });

        if (!item_exist) {
            items.push({
                product_id: $('#product_id').val(),
                product_name: $('#product-display').val(),
                cogs: $('#cogs').val(),
                price: $('#selling_price').val(),
                discount: 0,
                qty: $('#qty').val(),
                unit: $('#unit').val(),
                subtotal: parseInt($('#qty').val()) * parseInt($('#cogs').val())
            });
        }

        $('#product_id').val('');
        $('#product-display').val('');
        $('#cogs').val('');
        $('#selling_price').val('');
        $('#qty').val('');
        $('#unit').val('');

        //focus on #product-input
        $('#product-display').focus();

        console.log(items);

        appendItemsToTable();
        countTotal();
    });

    //function append items to table
    function appendItemsToTable() {
        var html = '';
        $.each(items, function(index, value) {
            html += '<tr>';
            html += '<td>' + (index + 1) + '</td>';
            html += '<td>' + value.product_name + '</td>';
            html += '<td>' + value.qty + ' ' + value.unit + '</td>';
            html += `<td>
                        <button type="button" class="btn btn-sm btn-danger btn-item-remove" data-id="` + index + `">
                            <i class="c-icon cil-trash"></i>
                        </button>
                    </td>`;
            html += '</tr>';
        });
        $('#table-items tbody').html(html);
    }

    // action .btn-item-remove
    $(document).on('click', '.btn-item-remove', function() {
        var id = $(this).data('id');

        // delete item from items
        items.splice(id, 1);

        console.log(items);

        appendItemsToTable();
        countTotal();
    });

    // count total
    function countTotal() {
        var total = 0;
        $.each(items, function(index, value) {
            total += parseInt(value.subtotal);
        });
        $('#total').val(total);
    }

    $('#stock-out').submit(function(e) {
        e.preventDefault();

        // check if items is empty
        if (items.length == 0) {
            swal('Oops', 'Masukkan barang terlebih dahulu', 'error');
            return false;
        }

        // creeate json data
        var data = {
            'invoice_no': $('#code').val(),
            'type': 'move',
            'cashier_log_id': null,
            'date': $('#action_date').val(),
            'user_id': $('#user_id').val(),
            'customer': null,
            'items': items,
            'total': $('#total').val(),
            'discount': 0,
            'grand_total': $('#total').val(),
            'payment_type': 1,
            'duedate': $('#due_date').val(),
            'pay': 0,
            'return': 0,
            'note': $('#note').val(),
        };

        // select button submit this form
        var button = $(this).find('button[type=submit]');
        button.html('Loading...');
        button.attr('disabled', true);

        // send ajax
        $.ajax({
            url: '<?= base_url('inventory/stock_out/store') ?>',
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function(response) {
                // swall alert
                swal({
                    title: 'Berhasil',
                    text: 'Stok keluar berhasil disimpan',
                    icon: "success",
                    button: "OK",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                }).then(function() {
                    // redirect
                    window.location.href = '<?= base_url('inventory/stock_out') ?>';
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
                button.html(`<i class="c-icon cil-save"></i><span>Simpan</span>`);
                button.attr('disabled', false);
            }
        });
    });
</script>
<?= $this->endSection('js'); ?>