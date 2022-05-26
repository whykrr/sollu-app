<!DOCTYPE html>
<!--
* CoreUI - Free Bootstrap Admin Template
* @version v3.4.0
* @link https://coreui.io
* Copyright (c) 2020 creativeLabs Łukasz Holeczek
* Licensed under MIT (https://coreui.io/license)
-->
<html lang="en">

<head>
    <base href="<?= base_url(); ?>/">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="description" content="Application">
    <meta name="author" content="Wahyu Kristiawan">
    <meta name="keyword" content="Application">
    <title>Sollu POS | Dashboard</title>
    <link rel="icon" type="image/png" href="aicons/logo-mini.png">
    <meta name="theme-color" content="#ffffff">
    <!-- Main styles for this application-->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
    <link href="whykrr/form-handler/css/form-handler.beta.css" rel="stylesheet">
    <link href="css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- Global site tag (gtag.js) - Google Analytics-->
    <link href="vendors/@coreui/chartjs/css/coreui-chartjs.css" rel="stylesheet">
    <link href="vendors/@coreui/icons/css/free.min.css" rel="stylesheet">
    <link href="css/tagify.css" rel="stylesheet" type="text/css" />
    <style>
        .typeahead .active .dropdown-item {
            background: #ebedef !important;

        }
    </style>
</head>

<body class="c-app">
    <?php if (@$blank) : ?>
        <?= $this->renderSection('main'); ?>
    <?php else : ?>
        <div class="c-sidebar c-sidebar-dark c-sidebar-fixed c-sidebar-lg-show" id="sidebar">
            <div class="c-sidebar-brand d-lg-down-none">
                <div class="c-sidebar-brand-full h2 mb-0">
                    <img src="aicons/logo.png" width="133" height="100" alt="Logo" style="margin:2px;">
                </div>
                <div class="c-sidebar-brand-minimized p-1">
                    <img src="aicons/logo-mini-dashboard.png" width="100%" height="100%" alt="Logo">
                </div>
            </div>

            <ul class="c-sidebar-nav">
                <?= view('layout/general_sidebar') ?>
            </ul>
            <button class="c-sidebar-minimizer c-class-toggler" type="button" data-target="_parent" data-class="c-sidebar-minimized"></button>
        </div>
        <div class="c-wrapper c-fixed-components">
            <header class="c-header c-header-light c-header-fixed c-header-with-subheader">
                <?= view('layout/general_topbar') ?>
                <?php if ($sidebar_active != 'cashier') : ?>
                    <div class="c-subheader px-3">
                        <?= $this->renderSection('breadcrumb'); ?>
                        <ol class="p-2 m-0 loading-page">
                            <div class="spinner-border" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </ol>
                    </div>
                <?php endif; ?>
            </header>
            <div class="c-body">
                <main class="c-main">
                    <?= $this->renderSection('main'); ?>
                </main>
                <footer class="c-footer">
                    <div>Sollu POS © <?= date('Y') ?></div>
                    <!-- <div class="ml-auto">Powered by&nbsp;<a href="https://coreui.io/">Colabs.id</a></div> -->
                </footer>
            </div>
        </div>
        <div class="modal right fade" id="modalSide" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Loading...</h4>
                    </div>
                    <div class="modal-body">
                        <div class="loading-center">
                            <div class="spinner-border" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-danger" type="button" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="position-fixed bottom-0 right-0 p-3" style="z-index: 5; right: 0; bottom: 0;">
            <div id="toastChat" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true" data-delay="5000">
                <div class="toast-header">
                    <strong class="mr-auto" id="toastHead">Bootstrap</strong>
                    <small id="toastTime"></small>
                    <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="toast-body">
                    Hello, world! This is a toast message.
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- CoreUI and necessary plugins-->
    <script src="js/jquery.min.js"></script>
    <!-- <script src="typeahead/dist/typeahead.jquery.min.js"></script> -->
    <script src="js/typeahead.js"></script>
    <script src="whykrr/form-handler/js/form-handler.beta.js"></script>
    <script src="js/sweetalert.min.js"></script>
    <script src="js/jquery.dataTables.min.js"></script>
    <script src="js/ckeditor.js"></script>
    <script src="vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
    <!--[if IE]><!-->
    <script src="vendors/@coreui/icons/js/svgxuse.min.js"></script>
    <!--<![endif]-->
    <!-- Plugins and scripts required by this view-->
    <script src="vendors/@coreui/chartjs/js/coreui-chartjs.bundle.js"></script>
    <script src="vendors/@coreui/utils/js/coreui-utils.js"></script>

    <!-- <script src="js/main.js"></script> -->
    <script src="js/sidepage.js"></script>
    <script src="js/ci4-datatablesbuilder.js"></script>
    <script src="js/ajaxform.js"></script>
    <script src="js/ckedtor-uploadadapter.js"></script>
    <script src="js/tagify/tagify.min.js"></script>
    <script src="js/tagify/tagify.polyfills.min.js"></script>
    <script>
        // load datatables
        $('.datatatable').DataTable({
            ordering: false,
        });

        // function MyCustomUploadAdapterPlugin(editor) {
        //     editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
        //         return new MyUploadAdapter(loader, base_url + "/cms/ckeditor/upload");
        //     };
        // }
        ckEditor();
        tagify();

        // function getToday
        function getToday() {
            var today = new Date();
            var dd = today.getDate();
            var mm = today.getMonth() + 1; //January is 0!
            var yyyy = today.getFullYear();

            if (dd < 10) {
                dd = '0' + dd;
            }

            if (mm < 10) {
                mm = '0' + mm;
            }

            today = yyyy + '-' + mm + '-' + dd;
            return today;
        }

        function tagify() {
            $('.tagify').each(function() {
                new Tagify(this, {
                    originalInputValueFormat: valuesArr => valuesArr.map(item => item.value).join('|')
                })
            })
        }

        function ckEditor() {
            editor = document.querySelectorAll('textarea.editor');
            editor.forEach(function(obj) {
                ClassicEditor
                    .create(obj, {
                        extraPlugins: [MyCustomUploadAdapterPlugin],
                        image: {
                            toolbar: ['imageTextAlternative', '|', 'imageStyle:alignLeft', 'imageStyle:full', 'imageStyle:alignRight'],

                            styles: [
                                'full',
                                'alignLeft',
                                'alignRight'
                            ]
                        },
                    })
                    .then(editor => {
                        console.log(editor);
                    })
                    .catch(error => {
                        console.error(error);
                    });
            });
        }

        // function reformat float to IDR
        function formatRupiah(angka, prefix) {
            var number_string = angka.replace(/[^,\d]/g, '').toString(),
                split = number_string.split(','),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            // tambahkan titik jika yang di input sudah menjadi angka ribuan
            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
            return prefix == undefined ? rupiah : (rupiah ? 'Rp. ' + rupiah : '');
        }
    </script>
    <?= $this->renderSection('js'); ?>

</body>

</html>