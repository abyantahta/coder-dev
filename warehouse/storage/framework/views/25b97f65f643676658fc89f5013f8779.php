<?php $__env->startSection('title','Master Item ATK'); ?>
<?php $__env->startSection('page-title','Master Item ATK'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item">Pengadaan</li>
    <li class="breadcrumb-item active">Item ATK</li>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('page-actions'); ?>
<div class="d-flex gap-2">
    <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
        <i class="bi bi-file-earmark-excel me-1"></i>Import Excel
    </button>
    <a href="<?php echo e(route('procurement.items.template')); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i>Template
    </a>
    <a href="<?php echo e(route('procurement.items.create')); ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah Item
    </a>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="itemTable">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th><th>Nama Item</th><th>UOM</th><th>Kategori</th><th>Harga</th><th>Status</th><th width="80">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><span class="fw-semibold text-primary"><?php echo e($item->item_code); ?></span></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if($item->photo): ?>
                                <img src="<?php echo e(asset('storage/'.$item->photo)); ?>" style="width:32px;height:32px;border-radius:6px;object-fit:cover;">
                                <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                                    <i class="bi bi-box text-muted" style="font-size:14px;"></i>
                                </div>
                                <?php endif; ?>
                                <?php echo e($item->name); ?>

                            </div>
                        </td>
                        <td><span class="badge bg-info bg-opacity-10 text-info"><?php echo e($item->uom?->code); ?></span></td>
                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?php echo e($item->category?->name ?? '-'); ?></span></td>
                        <td><?php echo e($item->price ? 'Rp ' . number_format($item->price, 0, ',', '.') : '-'); ?></td>
                        <td><span class="badge bg-<?php echo e($item->is_active ? 'success' : 'secondary'); ?>"><?php echo e($item->is_active ? 'Aktif' : 'Nonaktif'); ?></span></td>
                        <td>
                            <a href="<?php echo e(route('procurement.items.edit', $item->id)); ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada item ATK. Import dari Excel atau tambah manual.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3"><?php echo e($items->links('pagination::bootstrap-5')); ?></div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold"><i class="bi bi-file-earmark-excel me-2 text-success"></i>Import Item ATK</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo e(route('procurement.items.import')); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <div class="alert alert-info d-flex gap-2 py-2 mb-3">
                        <i class="bi bi-info-circle"></i>
                        <div style="font-size:13px;">
                            File Excel/CSV harus memiliki kolom:
                            <strong>kode_item, nama_item, uom, kategori</strong>
                            <br><a href="<?php echo e(route('procurement.items.template')); ?>" class="text-info">Download template</a>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih File</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-upload me-1"></i>Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
<?php if($items->count()): ?>
$('#itemTable').DataTable({
    paging: false, info: false,
    language: { search: 'Cari:', zeroRecords: 'Tidak ada data' },
    columnDefs: [{ orderable: false, targets: [6] }]
});
<?php endif; ?>
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/warehouse/resources/views/procurement/master/items/index.blade.php ENDPATH**/ ?>