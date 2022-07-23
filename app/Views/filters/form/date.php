<div class="col-md-6 filter filter-daily <?= $filter['type_filter'] == 'daily' ?: 'd-none' ?>">
    <label for="date">Tanggal</label>
    <input type="date" name="date" id="date" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= $filter['date'] ?>">
</div>