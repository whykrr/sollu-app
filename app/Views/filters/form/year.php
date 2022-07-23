<div class="col-md-3 filter filter-monthly <?= $filter['type_filter'] == 'monthly' ?: 'd-none' ?>">
    <label for="year">Tahun</label>
    <select name="year" id="year" class="form-control">
        <?php foreach ($years as $key => $item) : ?>
            <?php if ($filter['year'] == $key) : ?>
                <option value="<?= $item['year'] ?>" selected><?= $item['year'] ?></option>
            <?php else : ?>
                <option value="<?= $item['year'] ?>"><?= $item['year'] ?></option>
            <?php endif; ?>
        <?php endforeach; ?>
    </select>
</div>