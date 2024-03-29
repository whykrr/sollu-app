<?= $this->include('filters/form/type'); ?>
<?= $this->include('filters/form/month'); ?>
<?= $this->include('filters/form/year'); ?>
<?= $this->include('filters/form/start_date'); ?>
<?= $this->include('filters/form/end_date'); ?>
<?= $this->include('filters/form/date'); ?>
<div class="col-md-3 align-self-end">
    <div class="btn-toolbar" role="toolbar">
        <div class="btn-group mr-2" role="group">
            <a class="btn btn-danger" href="<?= empty($reset) ? $action : $reset ?>">Reset</a>
            <button type="submit" class="btn btn-primary">Cari</button>
        </div>
        <?php if ($export_button) : ?>
            <a class="btn btn-success export-data-btn" href="<?= "$action/export?" . http_build_query($filter) ?>">
                <span>Export Data</span>
            </a>
        <?php endif; ?>
    </div>
</div>
<script>
    // $(document).ready(function() {
    //     $base_action = "<?= "$action/export?" ?>"
    //     $('.filter input').change(function() {
    //         $('.filter input').each(function(e) {
    //             console.log(e.target.value)
    //         })
    //     })
    // })
</script>