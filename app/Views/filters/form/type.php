<div class="col-md-3">
    <div class="input-group">
        <div class="input-group-prepend">
            <div class="input-group-text">Tipe</div>
        </div>
        <select name="type_filter" id="type_filter" class="form-control form-filter" onchange="filterType(this)">
            <?php foreach ($types as $kt => $t) : ?>
                <option value="<?= $kt ?>" <?= $filter['type_filter'] == $kt ? 'selected' : null ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>