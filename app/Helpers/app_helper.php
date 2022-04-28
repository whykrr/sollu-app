<?php

/**
 * function verify env app.posType
 * 
 * @return bool
 */
function verifyPos($type)
{
    $posType = env('app.posType');

    // verify
    if ($posType == $type) {
        return true;
    }

    return false;
}
