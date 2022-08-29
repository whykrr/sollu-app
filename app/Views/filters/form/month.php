<div class="col-md-3 filter filter-monthly <?= $filter['type_filter'] == 'monthly' ?: 'd-none' ?>">
    <label for="month">Bulan</label>
    <select name="month" id="month" class="form-control form-filter">
        <?php foreach ($months as $key => $item) : ?>
            <?php if ($filter['month'] == $key) : ?>
                <option value="<?= $key ?>" selected><?= $item ?></option>
            <?php else : ?>
                <option value="<?= $key ?>"><?= $item ?></option>
            <?php endif; ?>
        <?php endforeach; ?>
    </select>
</div>