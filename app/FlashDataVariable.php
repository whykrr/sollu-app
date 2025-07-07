<?php

namespace App;

enum FlashDataVariable: string
{
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case FAILED  = 'failed';
}
