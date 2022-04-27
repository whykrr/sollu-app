<?php

namespace App\Models;

use CodeIgniter\Model;

class UserExtensionModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'users';
    protected $primaryKey           = 'id';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = false;
    protected $protectFields        = true;
    protected $allowedFields        = [];

    /**
     * datatables custom select
     */
    public function datatablesCustom(array $args)
    {
        // change return type as array
        $this->returnType = 'array';

        $custom = $this->select('users.*, g.description as group_name')
            ->join('auth_groups_users gu', 'gu.user_id = users.id', 'left')
            ->join('auth_groups g', 'g.id = gu.group_id', 'left');

        return $custom;
    }
}
