<?= $this->extend('auth/layout'); ?>

<?= $this->section('content'); ?>
<form action="<?= route_to('login') ?>" method="post" class="row justify-content-center">
    <?= csrf_field() ?>
    <div class="col-md-4">
        <div class="card-group">
            <div class="card p-4">
                <div class="card-body">
                    <h1>Login</h1>
                    <?= view('auth/_message_block') ?>
                    <p class="text-muted">Sign In to your account</p>
                    <?php if ($config->validFields === ['email']) : ?>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <svg class="c-icon">
                                        <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-user"></use>
                                    </svg>
                                </span>
                            </div>
                            <input name="login" class="form-control <?php if (session('errors.login')) : ?>is-invalid<?php endif ?>" type="email" placeholder="<?= lang('Auth.email') ?>">
                            <div class="invalid-feedback">
                                <?= session('errors.login') ?>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <svg class="c-icon">
                                        <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-user"></use>
                                    </svg>
                                </span>
                            </div>
                            <input name="login" class="form-control <?php if (session('errors.login')) : ?>is-invalid<?php endif ?>" type="text" placeholder="<?= lang('Auth.emailOrUsername') ?>">
                            <div class="invalid-feedback">
                                <?= session('errors.login') ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <svg class="c-icon">
                                    <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-lock-locked"></use>
                                </svg>
                            </span>
                        </div>
                        <input name="password" class="form-control <?php if (session('errors.password')) : ?>is-invalid<?php endif ?>" type="password" placeholder="<?= lang('Auth.password') ?>">
                        <div class="invalid-feedback">
                            <?= session('errors.password') ?>
                        </div>
                    </div>
                    <?php if ($config->allowRemembering) : ?>
                        <div class="form-check mb-2">
                            <label class="form-check-label">
                                <input type="checkbox" name="remember" class="form-check-input" <?php if (old('remember')) : ?> checked <?php endif ?>>
                                <?= lang('Auth.rememberMe') ?>
                            </label>
                        </div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-12">
                            <button class="btn btn-primary px-4" type="submit"><?= lang('Auth.loginAction') ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<?= $this->endSection('content'); ?>