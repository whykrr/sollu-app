<?php
if (!function_exists('formatStartDate')) {
    function formatStartDate(string $date)
    {
        return "{$date} 00:00:00";
    }
}

if (!function_exists('formatEndDate')) {
    function formatEndDate(string $date)
    {
        return "{$date} 23:59:59";
    }
}
