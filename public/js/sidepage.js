var base_url = $("base").attr("href");
var modalLoading = "";
const sidePage = {
    units: "masterdata/unit/form",
    product_category: "masterdata/product_category/form",
    product: "masterdata/product/form",
    user: "user/form",
};
$("#modalSide").modal({ backdrop: "static", show: false });
$("#modalSide").on("shown.coreui.modal", function (e) {
    var link = "";
    modal = $(this);
    modalContent = modal.find(".modal-content");
    modalPage = $(e.relatedTarget).data("page");

    // get sidepage keys
    var sidePageKeys = Object.keys(sidePage);
    if (sidePageKeys.includes(modalPage)) {
        link = base_url + sidePage[modalPage];
    } else {
        link = base_url + modalPage;
    }

    if ($(e.relatedTarget).attr("key")) {
        link = `${link}/` + $(e.relatedTarget).attr("key");
    }

    modalLoading = modalContent.html();
    modalContent.load(link, function (data) {
        ckEditor();
        tagify();
        modalContent.find("input[type=currency]").each(function () {
            executeCurrency(this);
        });
    });
});
$("#modalSide").on("hidden.coreui.modal", function () {
    modal = $(this);
    modal.find(".modal-content").html(modalLoading);
});
