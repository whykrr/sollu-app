var buttonSubmit = "Simpan";
const loading = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
Loading...`;

function loadingAjax(obj, action) {
    if (action === "show") {
        buttonSubmit = $(obj).find("button[type=submit]").html();
        $(obj).find(".modal-footer button").attr("disabled", true);
        $(obj).find("button[type=submit]").html(loading);
    } else {
        $(obj).find(".modal-footer button").attr("disabled", false);
        $(obj).find("button[type=submit]").html(buttonSubmit);
    }
}

$("body").on("submit", ".ajax", function (e) {
    e.preventDefault();
    form = $(this);
    uri_redirect = $(this).data("redirect");
    respondType = $(this).data("respond");
    loadingAjax(form, "show");
    $.ajax({
        type: "post",
        url: $(this).attr("action"),
        data: new FormData(this), //buat ngambil isi dari form
        contentType: false,
        cache: false,
        processData: false,
        dataType: "json",
        statusCode: {
            200: function (json) {
                switch (respondType) {
                    case "reload":
                        swal({
                            title: json.message,
                            icon: "success",
                            button: "Tutup",
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                        }).then(function () {
                            location.reload();
                        });
                        break;
                    case "redirect":
                        swal({
                            title: json.message,
                            icon: "success",
                            button: "Oke",
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                        }).then(function () {
                            alert;
                            location.href = uri_redirect;
                        });
                        break;
                    default:
                        $("#modalSide").modal("hide");
                        refreshDatatables(".datatable-builder");
                        swal({
                            title: json.message,
                            icon: "success",
                            button: "Tutup",
                        });
                }
            },
            400: function (resp) {
                //get json response
                json = resp.responseJSON;

                form.find(":input").removeClass("is-invalid");
                $.each(json.validation_error, function (key, data) {
                    form.find(":input[name=" + key + "]").addClass(
                        "is-invalid"
                    );
                    form.find(":input[name=" + key + "]")
                        .parent()
                        .find(".invalid-feedback")
                        .text(data);

                    form.find("#place_" + key).prepend(
                        '<div class="alert alert-danger clone-feedback">' +
                        data +
                        "</div>"
                    );
                });
            },
            500: function (resp) {
                //get json response
                json = resp.responseJSON;

                swal({
                    title: json.message,
                    message: "500",
                    icon: "error",
                    button: "Tutup",
                });
            },
        },
        complete: function (resp) {
            loadingAjax(form, "hide");
        },
    });
});

$("body").on("click", ".ajax-del", function () {
    url = {
        units: "masterdata/unit/delete",
        product_category: "masterdata/product_category/delete",
        product: "masterdata/product/delete",
        user: "user/delete",
        customer: "customer/delete",
        supplier: "inventory/supplier/delete",
        sales: "cashier/delete",
        stock_spoil: "inventory/stock_spoil/delete",
    };

    source = $(this).data("source");
    key = $(this).attr("key");
    swal({
        text: "Apakah anda yakin akan menghapus data ini?",
        icon: "warning",
        buttons: ["Tidak", "Ya"],
        dangerMode: true,
    }).then((willDelete) => {
        if (willDelete) {
            $.ajax({
                type: "DELETE",
                url: url[source] + "/" + key,
                success: function (data) {
                    swal(data.message, {
                        icon: "success",
                    });
                    $("#modalSide").modal("hide");
                    refreshDatatables(".datatable-builder");
                },
                error: function () {
                    swal({
                        title: "Gagal menghapus data",
                        text: "Periksa kembali koneksi anda !",
                        icon: "error",
                    });
                },
            });
        }
    });
});
