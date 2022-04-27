<?php

namespace App\Libraries;

use App\Models\SettingModel;

class Sidebar
{
    // cunstruct function
    public function __construct()
    {
        $this->model = new \App\Models\SidebarModel();
        // load helper
        helper('auth');
    }
    public function getSidebar()
    {
        // get instance authorize
        $setting = new SettingModel();

        $data = $this->model->read();

        // loop sidebar
        foreach ($data as $key => $menu) {
            if (!has_permission($menu['slug'])) {
                // remove this menu
                unset($data[$key]);

                // next loop
                continue;
            }
            foreach ($menu['submenu'] as $keySB => $submenu) {
                // concat slug submenu with slug menu 
                $data[$key]['submenu'][$keySB]['slug'] = $menu['slug'] . '-' . $submenu['slug'];

                // concat link submenu with link menu
                $data[$key]['submenu'][$keySB]['link'] = $menu['link'] . '/' . $submenu['link'];

                // check permission submenu
                if (!has_permission($data[$key]['submenu'][$keySB]['slug'])) {
                    // remove this submenu
                    unset($data[$key]['submenu'][$keySB]);
                }
            }
        }

        return $data;
    }
    /**
     * Get Sidebar Name
     * 
     * @param string $slug
     * @return string
     */
    public function getSidebarName($slug)
    {
        $data = $this->model->read();

        foreach ($data as $key => $menu) {
            if ($slug == 'dashboard') {
                return "Dashboard";
            }
            if (strpos($slug, $menu['slug']) !== false) {
                return $menu['name'];
            }
        }
    }
}
