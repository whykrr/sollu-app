const dtLanguage = "EN";

$("body").on("submit", ".form-dt-search", function () {
    source = $(this).data("dtesource");
    refreshDatatables("#dt-table-" + source);
    return false;
});

function getSearch(obj) {
    return $(obj).serializeArray();
}

function refreshDatatables(obj) {
    dataTable = $(obj).DataTable();
    dataTable.ajax.reload(null, true);
}

$(document).ready(function () {
    $(".datatable-builder").each(function () {
        dtObj = $(this);
        sourceUrl = dtObj.data("dteuri");
        source = dtObj.data("dtesource");
        col_title = dtObj.data("dtcoltitle");
        col_data = dtObj.data("dtcoldata");

        languageID = {
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            zeroRecords: "Data tidak ditemukan",
            info: "Halaman _PAGE_ dari _PAGES_ halaman",
            infoEmpty: "Tidak ada data tersedia",
            infoFiltered: "(disaring dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Lanjut",
                previous: "Kembali",
            },
        };

        languageEN = {
            lengthMenu: "Display _MENU_ records per page",
            zeroRecords: "Records not found",
            info: "Showing page _PAGE_ of _PAGES_",
            infoEmpty: "No records available",
            infoFiltered: "(disaring dari _MAX_ total data)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Prev",
            },
        };

        dtCtitle = col_title.split(",");
        dtCdata = col_data.split(",");
        dtColumn = [];

        for (let index = 0; index < dtCtitle.length; index++) {
            dtColumn.push({ title: dtCtitle[index], data: dtCdata[index] });
        }

        dtBuilder = dtObj.DataTable({
            dom: "Brltip",
            ordering: false,
            processing: true,
            serverSide: true,
            ajax: {
                url: sourceUrl,
                type: "GET",
                data: function (d) {
                    d.search_payload = getSearch(
                        "form#form-dt-search-" + source
                    );
                },
                statusCode: {
                    400: function (data) {
                        swal({
                            title: "Error - 400",
                            text: data.responseJSON.message,
                            icon: "error",
                        });
                    },
                    404: function (data) {
                        swal({
                            title: "Error - 404",
                            text: "request not found",
                            icon: "error",
                        });
                    },
                    500: function (data) {
                        swal({
                            title: "Error - 500",
                            text: data.responseJSON.message,
                            icon: "error",
                        });
                    },
                },
            },
            columns: dtColumn,
            language: window["language" + dtLanguage],
        });

        $.fn.dataTable.ext.errMode = "none";
    });
});
