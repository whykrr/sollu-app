<li class="c-sidebar-nav-item">
    <a class="c-sidebar-nav-link <?= ($sidebar_active == 'dashboard' ? 'c-active' : '') ?>" href="<?= base_url('dashboard'); ?>">
        <div class="c-sidebar-nav-icon">
            <i class="c-icon cil-speedometer"></i>
        </div>
        Dashboard
    </a>
</li>
<?php foreach (getSidebar() as $menu) : ?>
    <?php if (count($menu['submenu']) == 0) : ?>
        <li class="c-sidebar-nav-item">
            <a class="c-sidebar-nav-link <?= checkSidebarActive($menu["slug"], $sidebar_active) ?>" href="<?= base_url($menu['link']); ?>">
                <div class="c-sidebar-nav-icon">
                    <i class="<?= $menu['icon'] ?>"></i>
                </div>
                <?= $menu['name'] ?>
            </a>
        </li>
    <?php else : ?>
        <li class="c-sidebar-nav-item c-sidebar-nav-dropdown <?= checkSidebarShow($menu["slug"], $sidebar_active) ?>">
            <a class="c-sidebar-nav-link c-sidebar-nav-dropdown-toggle <?= checkSidebarActive($menu["slug"], $sidebar_active) ?>" href="#<?= $menu['name'] ?>">
                <div class="c-sidebar-nav-icon">
                    <i class="<?= $menu['icon'] ?>"></i>
                </div>
                <?= $menu['name'] ?>
            </a>
            <ul class="c-sidebar-nav-dropdown-items">
                <?php foreach ($menu['submenu'] as $submenu) : ?>
                    <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link <?= checkSidebarActive($submenu["slug"], $sidebar_active) ?>" href="<?= base_url($submenu['link']); ?>"> <?= $submenu['name'] ?></a></li>
                <?php endforeach; ?>
            </ul>
        </li>
    <?php endif; ?>
<?php endforeach; ?>