<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barcode</title>
</head>

<style>
    @page {
        size: A4 landscape;
        margin: 0;
    }

    @media print {
        body {
            max-width: 297mm;
            max-height: 210mm;
            overflow: hidden;
            page-break-after: always;
            /* margin: 30mm 45mm 30mm 45mm; */
            /* change the margins as you want them to be. */
        }
    }
</style>

<body onload="window.print()" onafterprint="history.back();">
    <?php
    for ($i = 0; $i < $loop; $i++) {
    ?>
        <div style="width: 12%; float: left; padding-right: 25px; padding-bottom: 10px;">
            <div style="margin-bottom: 5px;"><?= $name ?></div>
            <?= $barcode ?>
            <?= $code ?>
        </div>
    <?php
    }
    ?>

</body>

</html>