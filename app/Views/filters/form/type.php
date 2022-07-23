<div class="col-md-3">
    <label for="type_filter">Tipe Filter</label>
    <select name="type_filter" id="type_filter" class="form-control" onchange="filterType(this)">
        <?php foreach ($types as $kt => $t) : ?>
            <option value="<?= $kt ?>" <?= $filter['type_filter'] == $kt ? 'selected' : null ?>><?= $t ?></option>
        <?php endforeach; ?>
    </select>
</div>