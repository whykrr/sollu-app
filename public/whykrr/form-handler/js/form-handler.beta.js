/* Fungsi formatRupiah */
function formatRupiah(angka, prefix) {
    if (angka) {
        var number_string = angka.replace(/[^,\d]/g, "").toString(),
            split = number_string.split(","),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        // tambahkan titik jika yang di input sudah menjadi angka ribuan
        if (ribuan) {
            separator = sisa ? "." : "";
            rupiah += separator + ribuan.join(".");
        }

        rupiah = split[1] != undefined ? rupiah + "," + split[1] : rupiah;
        return prefix == undefined ? rupiah : rupiah ? "Rp. " + rupiah : "";
    }
}
function executeCurrency(target) {
    // get value input
    var value = $(target).val();

    // get data prefix value
    var prefix = $(target).data("prefix");

    if (prefix) {
        textResult = formatRupiah(value, prefix);
    } else {
        textResult = formatRupiah(value, "");
    }

    // check tag small after form
    var tagSmall = $(target).next("small");

    if (tagSmall.length != 0) {
        tagSmall.html(textResult);
    } else {
        // add small after event
        $(target).after(
            '<small class="form-text text-muted text-right">' +
                textResult +
                "</small>"
        );
    }
}

// Add Jquery Lib Function
(function ($) {
    // Restricts input for the set of matched elements to the given inputFilter function.
    $.fn.inputFilter = function (inputFilter) {
        return this.on(
            "input keydown keyup mousedown mouseup select contextmenu drop",
            'input[type="currency"]',
            function () {
                if (inputFilter(this.value)) {
                    this.oldValue = this.value;
                    this.oldSelectionStart = this.selectionStart;
                    this.oldSelectionEnd = this.selectionEnd;
                } else if (this.hasOwnProperty("oldValue")) {
                    this.value = this.oldValue;
                    this.setSelectionRange(
                        this.oldSelectionStart,
                        this.oldSelectionEnd
                    );
                } else {
                    this.value = "";
                }
            }
        );
    };
})(jQuery);
$("body").inputFilter(function (value) {
    return /^\d*$/.test(value); // Allow digits only, using a RegExp
});

/* Event Keyup pada .form-currency */
$("body").on("keyup", 'input[type="currency"]', function () {
    executeCurrency(this);
});
