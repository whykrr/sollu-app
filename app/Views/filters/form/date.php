<div class="col-md-6 filter filter-daily <?= $filter['type_filter'] == 'daily' ?: 'd-none' ?>">
    <div class="input-group">
        <div class="input-group-prepend">
            <div class="input-group-text">Tanggal</div>
        </div>
        <input type="date" name="date" id="date" class="form-control form-filter" max="<?= date('Y-m-d') ?>" value="<?= $filter['date'] ?>">
    </div>
</div>