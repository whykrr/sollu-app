<?= $this->extend('auth/layout'); ?>

<?= $this->section('content'); ?>
<form action="<?= route_to('register') ?>" method="post" class="row justify-content-center">
    <?= csrf_field() ?>
    <div class="col-md-6">
        <div class="card mx-4">
            <div class="card-body p-4">
                <h1>Register</h1>
                <?= view('auth/_message_block') ?>
                <p class="text-muted">Create your account</p>
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <svg class="c-icon">
                                <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-user"></use>
                            </svg>
                        </span>
                    </div>
                    <input class="form-control <?php if (session('errors.username')) : ?>is-invalid<?php endif ?>" type="text" placeholder="Username" name="username" value="<?= old('username') ?>">
                </div>
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <svg class="c-icon">
                                <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-user"></use>
                            </svg>
                        </span>
                    </div>
                    <input class="form-control <?php if (session('errors.name')) : ?>is-invalid<?php endif ?>" type="text" placeholder="Name" name="name" value="<?= old('name') ?>">
                </div>
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <svg class="c-icon">
                                <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-envelope-open"></use>
                            </svg>
                        </span>
                    </div>
                    <input class="form-control <?php if (session('errors.email')) : ?>is-invalid<?php endif ?>" type="text" placeholder="Email" name="email" value="<?= old('email') ?>">
                </div>
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <svg class="c-icon">
                                <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-lock-locked"></use>
                            </svg>
                        </span>
                    </div>
                    <input class="form-control <?php if (session('errors.password')) : ?>is-invalid<?php endif ?>" type="password" placeholder="Password" name="password">
                </div>
                <div class="input-group mb-4">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <svg class="c-icon">
                                <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-lock-locked"></use>
                            </svg>
                        </span>
                    </div>
                    <input class="form-control <?php if (session('errors.pass_confirm')) : ?>is-invalid<?php endif ?>" type="password" placeholder="Repeat password" name="pass_confirm">
                </div>
                <button class="btn btn-block btn-success" type="submit"><?= lang('Auth.register') ?></button>
            </div>
            <div class="card-footer p-4">
                <p class="pt-0 pb-2 m-0">Have a account ?</p>
                <a href="<?= route_to('login') ?>" class="btn btn-primary btn-block" type="button">Login Here</a>
            </div>
        </div>
    </div>
</form>
<?= $this->endSection('content'); ?>