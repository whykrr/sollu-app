<?php

namespace App\Models;

class SidebarModel
{
    protected $json = ROOTPATH . '/sidebar.json';
    function read()
    {
        $json = file_get_contents($this->json);
        $data = json_decode($json, true);
        return $data;
    }
}
