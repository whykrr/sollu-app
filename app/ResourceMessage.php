<?php

namespace App;

enum ResourceMessage: string
{
    case CREATE_SUCCESS  = 'Data was created!';
    case UPDATE_SUCCESS  = 'Data was updated!';
    case DELETE_SUCCESS  = 'Data moved to trash!';
    case RESTORE_SUCCESS = 'Data was restored!';
    case PURGE_SUCCESS   = 'Data was permanently deleted!';
}
