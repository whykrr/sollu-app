<div class="col-md-3 filter filter-range <?= $filter['type_filter'] == 'range' ?: 'd-none' ?>">
    <div class="input-group">
        <div class="input-group-prepend">
            <div class="input-group-text">Tanggal Akhir</div>
        </div>
        <input type="date" name="end_date" id="end_date" class="form-control form-filter" max="<?= date('Y-m-d') ?>" value="<?= $filter['end_date'] ?>">
    </div>
</div>