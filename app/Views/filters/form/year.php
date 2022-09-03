<div class="col-md-3 filter filter-monthly <?= $filter['type_filter'] == 'monthly' ?: 'd-none' ?>">
    <div class="input-group">
        <div class="input-group-prepend">
            <div class="input-group-text">Tahun</div>
        </div>
        <select name="year" id="year" class="form-control form-filter">
            <?php foreach ($years as $key => $item) : ?>
                <?php if ($filter['year'] == $key) : ?>
                    <option value="<?= $item['year'] ?>" selected><?= $item['year'] ?></option>
                <?php else : ?>
                    <option value="<?= $item['year'] ?>"><?= $item['year'] ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>
</div>