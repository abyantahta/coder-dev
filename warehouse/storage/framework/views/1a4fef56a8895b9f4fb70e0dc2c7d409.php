<?php $__env->startSection('title', 'Master Item'); ?>
<?php $__env->startSection('page-title', 'Master Item'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item">Master Data</li>
    <li class="breadcrumb-item active">Item</li>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('page-actions'); ?>
    <?php if(auth()->user()->isSuperAdmin()): ?>
    <form action="<?php echo e(route('master.items.sync')); ?>" method="POST" class="d-inline">
        <?php echo csrf_field(); ?>
        <button type="submit" class="btn btn-outline-success btn-sm me-2"
                onclick="return confirm('Sync data item dari QAD? Proses ini mungkin memakan waktu.')">
            <i class="bi bi-arrow-repeat me-1"></i>Sync dari QAD
        </button>
    </form>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="itemTable">
                <thead class="table-light">
                    <tr>
                        <th>Item Code</th>
                        <th>Deskripsi</th>
                        <th>Kategori</th>
                        <th>Satuan</th>
                        <th>Tipe</th>
                        <th>Stok</th>
                        <th>Min. Stok</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><span class="fw-semibold text-primary"><?php echo e($item->item_code); ?></span></td>
                        <td>
                            <div class="d-flex gap-2 align-items-center">
                                <?php if($item->photo): ?>
                                <img src="<?php echo e(asset('storage/'.$item->photo)); ?>" style="width:32px;height:32px;border-radius:6px;object-fit:cover;">
                                <?php endif; ?>
                                <?php echo e($item->description); ?>

                            </div>
                        </td>
                        <td><span class="badge bg-secondary bg-opacity-15 text-secondary"><?php echo e($item->category ?? '-'); ?></span></td>
                        <td><?php echo e($item->unit ?? '-'); ?></td>
                        <td>
                            <?php if($item->is_memo): ?>
                                <span class="badge bg-info bg-opacity-10 text-info">Memo</span>
                            <?php else: ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary">QAD</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $qty = $item->getQtyOnHand(); ?>
                            <span class="<?php echo e($qty <= 0 ? 'text-danger fw-bold' : 'text-success'); ?>">
                                <?php echo e(number_format($qty, 0)); ?>

                            </span>
                        </td>
                        <td><?php echo e($item->minimumStock ? number_format($item->minimumStock->min_qty, 0) : '-'); ?></td>
                        <td>
                            <span class="badge bg-<?php echo e($item->is_active ? 'success' : 'secondary'); ?>">
                                <?php echo e($item->is_active ? 'Aktif' : 'Nonaktif'); ?>

                            </span>
                        </td>
                        <td>
                            <a href="<?php echo e(route('master.items.edit', $item->id)); ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Belum ada item. Sync dari QAD untuk memuat data.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3"><?php echo e($items->links('pagination::bootstrap-5')); ?></div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
<?php if($items->count()): ?>
$('#itemTable').DataTable({
    paging: false, info: false,
    language: { search: 'Cari:', zeroRecords: 'Tidak ada data' },
    columnDefs: [{ orderable: false, targets: [8] }]
});
<?php endif; ?>
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/warehouse/resources/views/master/items/index.blade.php ENDPATH**/ ?>