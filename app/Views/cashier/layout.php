<?= $this->extend('layout/general_layout'); ?>

<?= $this->section('main'); ?>
<div class="container-fluid">
    <div class="fade-in">
        <div class="row">
            <?php if ($with_log == 1) : ?>
                <div class="col-md-12">
                    <button class="btn btn-info mb-2" id="add-cashier-panel">
                        Tambah Transaksi
                    </button>
                    <button id="open_cashier" data-toggle="modal" data-target="#modalSide" data-page="cashier/start_cashier" style="display: none;"></button>
                    <button class="btn btn-danger mb-2 float-right" data-toggle="modal" data-target="#modalSide" data-page="cashier/end_cashier">
                        <span>Tutup Kasir</span>
                    </button>

                    <input type="hidden" id="cashier_log_id" value="<?= $cashier_log_id ?>">
                    <input type="hidden" id="with_log" value="<?= $with_log ?>">
                </div>
            <?php endif; ?>
            <div class="col-md-12">
                <div class="nav-tabs-boxed" style="border: none;">
                    <ul class="nav nav-tabs" role="tablist" id="tab-cashier-panel">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#tr1" role="tab" aria-controls="home" id="tab-tr-1">Transaksi</a>
                        </li>
                    </ul>
                    <div class="tab-content p-0" style="border: none;" id="tab-content-cashier-panel">
                        <div class="tab-pane active" id="tr1" role="tabpanel">
                            <iframe src="<?= base_url('cashier/panel') ?>" width="100%" style="border: none;"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?= $this->endSection('main'); ?>
<?= $this->section('js'); ?>
<script>
    var panelHeight = 0;
    check_log();

    // document ready function
    $(document).ready(function() {
        $('.c-sidebar-minimizer').click();
    })

    function check_log() {
        with_log = $('#with_log').val();
        log_id = $('#cashier_log_id').val();

        if (with_log == 1 && log_id == '') {
            $('#open_cashier').click();
        }
    }

    function fullscreenPanel(obj) {

    }

    function makeid(length) {
        var result = '';
        var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        var charactersLength = characters.length;
        for (var i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() *
                charactersLength));
        }
        return result;
    }

    $('#add-cashier-panel').click(function() {
        // get random string alphabet only
        id = makeid(10);

        $('#tab-cashier-panel').append(`
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#` + id + `" role="tab" aria-controls="messages">Transaksi
                    <span class="close-cashier-panel badge bg-pill bg-danger text-white">x</span>
                </a>
            </li>
        `);

        // add content
        $('#tab-content-cashier-panel').append(`
            <div class="tab-pane" id="` + id + `" role="tabpanel">
                <iframe src="<?= base_url('cashier/panel') ?>" width="100%" height="` + panelHeight + `" style="border: none;"></iframe>
            </div>
        `);
        $("div#" + id).find('iframe').trigger('load');
    });

    // action close-cashier-panel
    $('body').on('click', '.close-cashier-panel', function() {
        // get href
        href = $(this).parent().attr('href');

        $(this).parent().parent().remove();
        // remove tab content
        $('.tab-content').find(href).remove();
    });

    $('iframe').on('load', function() {
        // get content height
        var contentHeight = $(this).contents().find('body').height();
        panelHeight = contentHeight;
        // set iframe height
        $(this).height(contentHeight);
    });
</script>
<?= $this->endSection('js'); ?>