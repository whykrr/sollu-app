<?php if (session()->has('message')) : ?>
    <div class="alert alert-success mb-2 px-3 py-2">
        <?= session('message') ?>
    </div>
<?php endif ?>

<?php if (session()->has('error')) : ?>
    <div class="alert alert-danger mb-2 px-3 py-2">
        <?= session('error') ?>
    </div>
<?php endif ?>

<?php if (session()->has('errors')) : ?>
    <ul class="alert alert-danger mb-2 px-3 py-2">
        <?php foreach (session('errors') as $error) : ?>
            <li><?= $error ?></li>
        <?php endforeach ?>
    </ul>
<?php endif ?>