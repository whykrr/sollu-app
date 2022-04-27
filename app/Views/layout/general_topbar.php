<button class="c-header-toggler c-class-toggler d-lg-none mfe-auto" type="button" data-target="#sidebar" data-class="c-sidebar-show">
    <svg class="c-icon c-icon-lg">
        <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-menu"></use>
    </svg>
</button>
<button class="c-header-toggler c-class-toggler mfs-3 d-md-down-none" type="button" data-target="#sidebar" data-class="c-sidebar-lg-show" responsive="true">
    <svg class="c-icon c-icon-lg">
        <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-menu"></use>
    </svg>
</button>
<div class="c-header-nav">
    <h2 class="m-0 mr-2"><?= getSidebarName($sidebar_active) ?></h2>
</div>
<ul class="c-header-nav ml-auto mr-4">
    <li class="c-header-nav-item dropdown">
        <a class="c-header-nav-link" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
            <div class="mr-2 h5 m-0">
                <?= ucwords(user()->name) ?>
            </div>
            <?php if (user()->photo != null) : ?>
                <div class="c-avatar"><img class="c-avatar-img" src="uploads/<?= user()->photo ?>" alt="user@email.com"></div>
            <?php else : ?>
                <div class="c-avatar"><img class="c-avatar-img" src="assets/img/default-avatar.png" alt="user@email.com"></div>
            <?php endif; ?>
        </a>
        <div class="dropdown-menu dropdown-menu-right pt-0">
            <div class="dropdown-header bg-light py-2"><strong>Settings</strong></div>
            <a class="dropdown-item" href="<?= base_url('setting/profile'); ?>">
                <div class="c-icon mr-2">
                    <i class="c-icon cil-user"></i>
                </div>
                Profile
            </a>
            <a class="dropdown-item" href="<?= base_url('logout'); ?>">
                <div class="c-icon mr-2">
                    <i class="c-icon cil-account-logout"></i>
                </div>
                Logout
            </a>
        </div>
    </li>
</ul>