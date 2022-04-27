<?= $this->extend('layout/general_layout'); ?>

<?= $this->section('breadcrumb'); ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb my-0 ms-2">
        <li class="breadcrumb-item"><span>Inventory</span></li>
        <li class="breadcrumb-item"><span>Pembelian Stok</span></li>
        <li class="breadcrumb-item active"><span>Pembelian Baru</span></li>
    </ol>
</nav>
<?= $this->endSection('breadcrumb'); ?>

<?= $this->section('main'); ?>
<div class="container-fluid">
    <div class="fade-in">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Form Pembelian Baru</h5>
            </div>
            <?php
            $invoiceCode = 'INV-SP-' . rand(101, 999) . date('his') . '-' . date('dmy');
            ?>
            <div class="card-body">
                <form class="row ajax" action="<?php echo base_url('inventory/stock_purchase/store'); ?>" data-respond="redirect" data-redirect="<?= base_url('inventory/stock_purchase') ?>">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code">Nomor Invoice</label>
                            <input type="text" name="invoice_no" class="form-control" id="code" placeholder="<?= $invoiceCode ?>">
                            <input type="hidden" name="invoice_no_generate" value="<?= $invoiceCode ?>">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group">
                            <label for="supplier">Supplier</label>
                            <input type="text" name="supplier" class="form-control" id="supplier" placeholder="PT. Maju Jaya">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="date">Tanggal</label>
                            <input type="date" name="date" id="date" class="form-control" value="<?= date('Y-m-d') ?>">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="form-group">
                            <label for="note">Catatan</label>
                            <textarea name="note" id="note" class="form-control"></textarea>
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
                                    <label for="cogs">Harga Beli</label>
                                    <input type="currency" class="form-control form-item" id="cogs" data-prefix="Rp. ">
                                </div>
                                <div class="form-group mb-1">
                                    <label for="selling_price">Harga Jual</label>
                                    <input type="currency" class="form-control form-item" id="selling_price" data-prefix="Rp. ">
                                </div>
                                <div class="form-group mb-1">
                                    <label for="qty">Qty</label>
                                    <input type="number" class="form-control form-item" id="qty" placeholder="Qty">
                                    <input type="hidden" id="unit">
                                </div>
                                <div class="form-group mb-1 ">
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
                                <h5 class="card-title mb-0"><strong>List Pembelian</strong></h5>
                            </div>
                            <div class="card-body p-1">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" id="table-items">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Produk</th>
                                                <th>Harga Beli</th>
                                                <th>Harga Jual</th>
                                                <th>Qty</th>
                                                <th>Subtotal</th>
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
                        <a href="<?= base_url('inventory/stock_purchase') ?>" class="btn btn-danger btn-block">
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
                    <input type="hidden" name="items" id="items-form">
                    <input type="hidden" name="total" id="total">
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
            $('#selling_price').val(parseInt(item.selling_price));
            $('#cogs').focus();
            $('#qty').val(1);
            $('#unit').val(item.unit);

            executeCurrency('#cogs');
            executeCurrency('#selling_price');
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

    // action on enter #cogs
    $('#cogs').keypress(function(e) {
        e.preventDefault();
        if (e.which == 13) {
            $('#selling_price').focus();
        }
    });

    // action on enter #selling_price
    $('#selling_price').keypress(function(e) {
        e.preventDefault();
        if (e.which == 13) {
            $('#qty').focus();
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

        if ($('#cogs').val() == '') {
            alert('Harga beli harus diisi');
            $('#cogs').focus();
            return false;
        }

        if ($('#selling_price').val() == '') {
            alert('Harga jual harus diisi');
            $('#selling_price').focus();
            return false;
        }

        if ($('#qty').val() == '') {
            alert('Qty harus diisi');
            $('#qty').focus();
            return false;
        }

        // replace items if same product id
        var item_exist = false;
        $.each(items, function(index, value) {
            if (value.product_id == $('#product_id').val()) {
                item_exist = true;
                items[index].cogs = parseInt($('#cogs').val());
                items[index].selling_price = parseInt($('#selling_price').val());
                items[index].qty = parseInt(items[index].qty) + parseInt($('#qty').val());
                items[index].subtotal = parseInt(items[index].qty) * parseInt(items[index].cogs);
            }
        });

        if (!item_exist) {
            items.push({
                product_id: $('#product_id').val(),
                product_name: $('#product-display').val(),
                cogs: $('#cogs').val(),
                selling_price: $('#selling_price').val(),
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
        fillItemsForm();
        countTotal();
    });

    //function append items to table
    function appendItemsToTable() {
        var html = '';
        $.each(items, function(index, value) {
            html += '<tr>';
            html += '<td>' + (index + 1) + '</td>';
            html += '<td>' + value.product_name + '</td>';
            html += '<td>' + formatRupiah(value.cogs.toString(), true) + '</td>';
            html += '<td>' + formatRupiah(value.selling_price.toString(), true) + '</td>';
            html += '<td>' + value.qty + ' ' + value.unit + '</td>';
            html += '<td>' + formatRupiah(value.subtotal.toString(), true) + '</td>';
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
        fillItemsForm();
        countTotal();
    });

    // fill #items-from from items
    function fillItemsForm() {
        var items_form = '';
        $.each(items, function(index, value) {
            items_form += value.product_id + ',' + value.cogs + ',' + value.selling_price + ',' + value.qty + ';';
        });
        $('#items-form').val(items_form);
    }

    // count total
    function countTotal() {
        var total = 0;
        $.each(items, function(index, value) {
            total += parseInt(value.subtotal);
        });
        $('#total').val(total);
    }
</script>
<?= $this->endSection('js'); ?>