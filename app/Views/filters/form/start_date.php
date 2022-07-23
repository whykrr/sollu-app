<div class="col-md-3 filter filter-range <?= $filter['type_filter'] == 'range' ?: 'd-none' ?>">
    <label for="start_date">Tanggal Awal</label>
    <input type="date" name="start_date" id="start_date" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= $filter['start_date'] ?>">
</div>