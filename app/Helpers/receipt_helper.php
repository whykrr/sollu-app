<?php

/**
 * function receipt_align
 * 
 * @param string $text
 * @param string $align
 * @param string $max_length
 * 
 * @return string
 */
function receipt_align($text, $align = 'left', $max_length = 0)
{
    // check lenght $text if < $max_length
    if (strlen($text) <= $max_length) {
        // check align
        if ($align == 'left') {
            // align left
            $text = str_pad($text, $max_length, ' ', STR_PAD_RIGHT);
        } elseif ($align == 'right') {
            // align right
            $text = str_pad($text, $max_length, ' ', STR_PAD_LEFT);
        } elseif ($align == 'center') {
            // align center
            $text = str_pad($text, $max_length, ' ', STR_PAD_BOTH);
        }
    } else {
        // cut text
        $text = substr($text, 0, $max_length);
    }

    return $text;
}

/**
 * function receipt_align_multiline
 * 
 * @param string $text
 * @param string $align
 * @param string $max_length
 * 
 * @return string
 */
function receipt_align_multiline($text, $align = 'left', $max_length = 0)
{
    // get total character in text
    $total_character = strlen($text);
    $atext = [];

    for ($offset = 0; $offset < $total_character; $offset = $offset + $max_length) {
        array_push($atext, receipt_align(substr($text, $offset, $max_length), $align, $max_length));
    }

    return implode("\n", $atext);
}


/**
 * function receipt_separator
 * 
 * @param string $string
 * @param string $max_length
 * 
 * @return string
 */
function receipt_separator($string, $max_length = 0)
{
    return str_pad('', $max_length, $string, STR_PAD_BOTH) . "\n";
}

/**
 * function receipt_repeater
 * 
 * @param string $string
 * @param string $max_length
 * 
 * @return string
 */
function receipt_repeater($string, $max_length = 0)
{
    return str_pad('', $max_length, $string, STR_PAD_BOTH);
}
