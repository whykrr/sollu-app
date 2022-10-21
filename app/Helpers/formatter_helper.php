<?php

/**
 * formating int to format IDR
 * 
 * @param int $number
 * @return string
 */
if (!function_exists('formatIDR')) {
    function formatIDR($value)
    {
        // remormat string $value to float

        $value = floatval($value);
        return 'Rp. ' . number_format($value, 0, ',', '.');
    }
}

/**
 * formating int to format IDR
 * 
 * @param int $number
 * @return string
 */
if (!function_exists('formatIDRHidden')) {
    function formatIDRHidden($value)
    {
        // remormat string $value to float
        if (in_groups('user') || in_groups('warehouse')) {
            return 'Rp. xxx';
        } else {
            return 'Rp. ' . number_format($value, 0, ',', '.');
        }
    }
}

/**
 * formatting status 1 to active and 0 to inactive
 * 
 * @param int $value
 * @return string
 */
if (!function_exists('formatStatus')) {
    function formatStatus($value)
    {
        return $value == 1 ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Tidak Aktif</span>';
    }
}

/**
 * formatting status Debt and Credit 1 to active and 0 to inactive
 * 
 * @param int $value
 * @return string
 */
if (!function_exists('formatStatusDC')) {
    function formatStatusDC($value)
    {
        return $value == 1 ? '<span class="badge badge-success">Lunas</span>' : '<span class="badge badge-danger">Belum Lunas</span>';
    }
}

/**
 * concat stock with unit
 * 
 * @param int $value
 * @param string $unit
 * @return string
 */
if (!function_exists('formatStock')) {
    function formatStock($value, $unit)
    {
        return $value . ' ' . $unit;
    }
}

/**
 * formatting date to (Y/m/d)
 * 
 * @param string $value
 * @return string
 */
if (!function_exists('formatDateSimple')) {
    function formatDateSimple($value)
    {
        // make sure date is Y-m-d
        $value = date('Y-m-d', strtotime($value));

        return date('d-m-Y', strtotime($value));
    }
}

/**
 * formatting datetime to (Y/m/d H:i:s)
 * 
 * @param string $value
 * @return string
 */
if (!function_exists('formatDateTimeSimple')) {
    function formatDateTimeSimple($value)
    {
        // make sure date is Y-m-d
        $value = date('Y-m-d H:i:s', strtotime($value));

        return date('d-m-Y H:i:s', strtotime($value));
    }
}

/**
 * formatting date spell indonesian 
 * 
 * @param string $value
 * @return string
 */
if (!function_exists('formatDateID')) {
    function formatDateID($value)
    {
        // make sure date is Y-m-d
        $value = date('Y-m-d', strtotime($value));

        // array month with bahasa indonesia
        $month = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        ];
        // split date
        $dateSplit = explode('-', $value);

        return $dateSplit[2] . ' ' . $month[$dateSplit[1]] . ' ' . $dateSplit[0];
    }
}

/**
 * formatting month spell indonesian 
 * 
 * @param string $value
 * @return string
 */
if (!function_exists('formatMonthID')) {
    function formatMonthID($value)
    {
        // array month with bahasa indonesia
        $month = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        ];

        return $month[$value];
    }
}


/**
 * formatting payment type string
 * 
 * @param int $code
 * @return string
 */
if (!function_exists('formatPaymentType')) {
    function formatPaymentType($code)
    {
        $paymentType = [
            0 => 'Cash',
            1 => 'Credit',
        ];

        return $paymentType[$code];
    }
}
