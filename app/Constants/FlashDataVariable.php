<?php

namespace App\Constants;

enum FlashDataVariable: string
{
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case FAILED  = 'failed';
}
