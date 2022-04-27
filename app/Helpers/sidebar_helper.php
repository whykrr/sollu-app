<?php

/**
 * # --------------------------------------------------------------------
 * * Mengambil data sidebar menu
 * # --------------------------------------------------------------------
 *
 * @return array
 */
function getSidebar()
{
    $libSidebar = new \App\Libraries\Sidebar();
    return $libSidebar->getSidebar();
}

function checkSidebarSlug($slug, $sidebar_active)
{
    if ($slug == $sidebar_active) {
        return true;
    } else if (strpos($sidebar_active, $slug) !== false) {
        return true;
    } else {
        return false;
    }
}

/** 
 * # --------------------------------------------------------------------
 * * Check Active Sidebar
 * # --------------------------------------------------------------------
 * 
 * @param string $slug
 * @param string $sidebar_active
 * @return string
 */
function checkSidebarActive($slug, $sidebar_active)
{
    if (checkSidebarSlug($slug, $sidebar_active)) {
        return 'c-active';
    } else {
        return '';
    }
}

/** 
 * # --------------------------------------------------------------------
 * * Check Show Sidebar
 * # --------------------------------------------------------------------
 * 
 * @param string $slug
 * @param string $sidebar_active
 * @return string
 */
function checkSidebarShow($slug, $sidebar_active)
{
    if (checkSidebarSlug($slug, $sidebar_active)) {
        return 'c-show';
    } else {
        return '';
    }
}

/**
 * Get Sidebar Name
 * 
 * @param string $slug
 * @return string
 */
function getSidebarName($slug)
{
    $libSidebar = new \App\Libraries\Sidebar();
    return $libSidebar->getSidebarName($slug);
}
