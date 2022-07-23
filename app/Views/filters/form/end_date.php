<div class="col-md-3 filter filter-range <?= $filter['type_filter'] == 'range' ?: 'd-none' ?>">
    <label for="end_date">Tanggal Akhir</label>
    <input type="date" name="end_date" id="end_date" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= $filter['end_date'] ?>">
</div>