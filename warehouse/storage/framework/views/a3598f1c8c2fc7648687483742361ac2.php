<?php $__env->startSection('title', 'Departemen'); ?>
<?php $__env->startSection('page-title', 'Master Departemen'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item">Master Data</li>
    <li class="breadcrumb-item active">Departemen</li>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('page-actions'); ?>
    <?php if(auth()->user()->isAdmin()): ?>
    <a href="<?php echo e(route('master.departments.create')); ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah Departemen
    </a>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="deptTable">
                <thead class="table-light">
                    <tr>
                        <th width="5%">#</th>
                        <th width="15%">Kode</th>
                        <th>Nama Departemen</th>
                        <th width="12%">Jumlah User</th>
                        <th width="10%">Status</th>
                        <th width="12%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($i + 1); ?></td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary fw-semibold"><?php echo e($dept->code); ?></span></td>
                        <td><?php echo e($dept->name); ?></td>
                        <td>
                            <span class="badge bg-secondary"><?php echo e($dept->users_count); ?> user</span>
                        </td>
                        <td>
                            <?php if($dept->is_active): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if(auth()->user()->isAdmin()): ?>
                            <a href="<?php echo e(route('master.departments.edit', $dept->id)); ?>"
                               class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button onclick="deleteDept(<?php echo e($dept->id); ?>, '<?php echo e($dept->name); ?>')"
                                    class="btn btn-sm btn-outline-danger" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada departemen</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">
    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
</form>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
<?php if($departments->count()): ?>
$('#deptTable').DataTable({
    pageLength: 15,
    language: {
        search: 'Cari:', paginate: { previous: '&laquo;', next: '&raquo;' },
        lengthMenu: 'Tampilkan _MENU_ data', info: 'Menampilkan _START_-_END_ dari _TOTAL_ data',
        zeroRecords: 'Tidak ada data ditemukan', emptyTable: 'Belum ada data'
    }
});
<?php endif; ?>

function deleteDept(id, name) {
    Swal.fire({
        title: 'Hapus Departemen?',
        text: `Departemen "${name}" akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then(result => {
        if (result.isConfirmed) {
            const form = document.getElementById('deleteForm');
            form.action = `/master/departments/${id}`;
            form.submit();
        }
    });
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/warehouse/resources/views/master/departments/index.blade.php ENDPATH**/ ?>