<?php

namespace App;

enum ResourceMessage: string
{
    case CREATE_SUCCESS  = 'Data berhasil dibuat!';
    case UPDATE_SUCCESS  = 'Data berhasil diperbarui!';
    case DELETE_SUCCESS  = 'Data dipindah ke sampah!';
    case RESTORE_SUCCESS = 'Data berhasil di kembalikan!';
    case PURGE_SUCCESS   = 'Data berhasil di hapus!';
}
