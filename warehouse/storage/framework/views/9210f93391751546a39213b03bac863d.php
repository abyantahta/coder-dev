<?php $__env->startSection('title', 'Stok Minimum'); ?>
<?php $__env->startSection('page-title', 'Manajemen Stok Minimum'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item">Admin</li>
    <li class="breadcrumb-item active">Stok Minimum</li>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('page-actions'); ?>
    <?php if($belowMinCount > 0): ?>
    <form action="<?php echo e(route('admin.min-stock.generate-pr')); ?>" method="POST" class="d-inline">
        <?php echo csrf_field(); ?>
        <button type="submit" class="btn btn-warning btn-sm"
            onclick="return confirm('Generate Purchase Request untuk <?php echo e($belowMinCount); ?> item di bawah minimum?')">
            <i class="bi bi-file-earmark-plus me-1"></i>Generate PR (<?php echo e($belowMinCount); ?> item)
        </button>
    </form>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php if($belowMinCount > 0): ?>
<div class="alert alert-warning d-flex gap-2 align-items-center mb-4">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div>
        <strong><?php echo e($belowMinCount); ?> item</strong> berada di bawah stok minimum.
        Klik <strong>"Generate PR"</strong> untuk membuat Purchase Request ke Purchasing.
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><i class="bi bi-bar-chart me-2 text-primary"></i>Daftar Stok & Minimum</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="stockTable">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>Kategori</th>
                        <th>Tipe</th>
                        <th width="120">Stok Saat Ini</th>
                        <th width="130">Stok Minimum</th>
                        <th width="80">Aktif</th>
                        <th width="80">Status</th>
                        <th width="80">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="<?php echo e($item->below_minimum ? 'table-warning' : ''); ?>" id="row-<?php echo e($item->id); ?>">
                        <td>
                            <div class="fw-semibold"><?php echo e($item->description); ?></div>
                            <div class="text-muted" style="font-size:11px;"><?php echo e($item->item_code); ?></div>
                        </td>
                        <td><span class="badge bg-secondary bg-opacity-15 text-secondary"><?php echo e($item->category ?? '-'); ?></span></td>
                        <td>
                            <?php if($item->is_memo): ?>
                                <span class="badge bg-info bg-opacity-10 text-info">Memo</span>
                            <?php else: ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary">QAD</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="fw-bold <?php echo e($item->below_minimum ? 'text-danger' : 'text-success'); ?>">
                                <?php echo e(number_format($item->current_qty, 2)); ?>

                            </span>
                            <span class="text-muted" style="font-size:11px;"> <?php echo e($item->unit); ?></span>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control min-qty-input"
                                       id="minqty-<?php echo e($item->id); ?>"
                                       value="<?php echo e($item->minimumStock?->min_qty ?? 0); ?>"
                                       min="0" step="0.01" style="width:70px;">
                                <span class="input-group-text" style="font-size:11px;"><?php echo e($item->unit); ?></span>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-flex justify-content-center">
                                <input class="form-check-input active-toggle" type="checkbox"
                                       id="active-<?php echo e($item->id); ?>"
                                       <?php echo e($item->minimumStock?->is_active ? 'checked' : ''); ?>>
                            </div>
                        </td>
                        <td>
                            <?php if($item->below_minimum): ?>
                                <span class="badge bg-danger">Rendah</span>
                            <?php elseif($item->minimumStock): ?>
                                <span class="badge bg-success">Aman</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="saveMinStock(<?php echo e($item->id); ?>)">
                                <i class="bi bi-save"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
$('#stockTable').DataTable({
    pageLength: 20,
    language: { search: 'Cari:', paginate: { previous: '&laquo;', next: '&raquo;' }, info: 'Menampilkan _START_-_END_ dari _TOTAL_', zeroRecords: 'Tidak ada data' },
    columnDefs: [{ orderable: false, targets: [4,5,7] }]
});

async function saveMinStock(itemId) {
    const minQty   = document.getElementById(`minqty-${itemId}`).value;
    const isActive = document.getElementById(`active-${itemId}`).checked;

    const res = await fetch(`/admin/min-stock/${itemId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
        body: JSON.stringify({ min_qty: minQty, is_active: isActive ? 1 : 0 })
    });
    const data = await res.json();

    if (data.success) {
        Swal.fire({ icon: 'success', title: 'Tersimpan', text: data.message,
            timer: 1500, showConfirmButton: false, toast: true, position: 'top-end' });
    } else {
        Swal.fire({ icon: 'error', title: 'Gagal', text: data.message });
    }
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/warehouse/resources/views/admin/min-stock/index.blade.php ENDPATH**/ ?>