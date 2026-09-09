<?php $__env->startSection('title', 'User Management'); ?>
<?php $__env->startSection('page-title', 'User Management'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item">Master Data</li>
    <li class="breadcrumb-item active">User</li>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('page-actions'); ?>
    <a href="<?php echo e(route('master.users.create')); ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus me-1"></i>Tambah User
    </a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="userTable">
                <thead class="table-light">
                    <tr>
                        <th width="5%">#</th>
                        <th>Nama / NPK</th>
                        <th>Email</th>
                        <th>Departemen</th>
                        <th>Jabatan</th>
                        <th width="10%">Status</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($i + 1); ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?php echo e($user->photo ? asset('storage/'.$user->photo) : asset('assets/images/avatar.png')); ?>"
                                     class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">
                                <div>
                                    <div class="fw-semibold"><?php echo e($user->name); ?></div>
                                    <div class="text-muted" style="font-size:12px;"><?php echo e($user->npk); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo e($user->email); ?></td>
                        <td><?php echo e($user->department?->name ?? '-'); ?></td>
                        <td>
                            <?php
                                $roleBadge = match($user->role) {
                                    'superadmin' => 'danger',
                                    'admin'      => 'warning',
                                    default      => 'secondary'
                                };
                            ?>
                            <span class="badge bg-<?php echo e($roleBadge); ?>"><?php echo e($user->getRoleLabel()); ?></span>
                        </td>
                        <td>
                            <?php if($user->is_active): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?php echo e(route('master.users.edit', $user->id)); ?>"
                                   class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button onclick="showQr(<?php echo e($user->id); ?>, '<?php echo e($user->name); ?>', '<?php echo e($user->npk); ?>')"
                                        class="btn btn-sm btn-outline-info" title="Lihat QR">
                                    <i class="bi bi-qr-code"></i>
                                </button>
                                <?php if($user->id !== auth()->id()): ?>
                                <button onclick="deleteUser(<?php echo e($user->id); ?>, '<?php echo e($user->name); ?>')"
                                        class="btn btn-sm btn-outline-danger" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada user</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- QR Modal -->
<div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:300px;">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h6 class="modal-title fw-bold mb-0" id="qrModalTitle"></h6>
                    <div class="text-muted" style="font-size:12px;" id="qrNpkLabel"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-3">
                <div style="width:220px;height:220px;margin:0 auto;border-radius:8px;border:1px solid #e9ecef;overflow:hidden;background:#fff;">
                    <img id="qrImage" src="" alt="QR Code" style="width:100%;height:100%;">
                </div>
                <p class="text-muted mt-2 mb-0" style="font-size:11px;">
                    <i class="bi bi-qr-code-scan me-1"></i>Scan untuk login ke sistem
                </p>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex gap-2">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Tutup</button>
                <a id="qrDownloadBtn" href="#" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-download me-1"></i>Download
                </a>
                <button type="button" class="btn btn-primary btn-sm" onclick="printQr()">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">
    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
</form>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
<?php if($users->count()): ?>
$('#userTable').DataTable({
    pageLength: 15,
    language: {
        search: 'Cari:', paginate: { previous: '&laquo;', next: '&raquo;' },
        lengthMenu: 'Tampilkan _MENU_ data', info: 'Menampilkan _START_-_END_ dari _TOTAL_ data',
        zeroRecords: 'Tidak ada data', emptyTable: 'Belum ada data'
    },
    columnDefs: [{ orderable: false, targets: [6] }]
});
<?php endif; ?>

let qrUser = { id: null, name: '', npk: '' };

function showQr(userId, name, npk) {
    qrUser = { id: userId, name, npk };

    document.getElementById('qrModalTitle').textContent = name;
    document.getElementById('qrNpkLabel').textContent   = 'NPK: ' + npk;

    // Reset dulu, lalu load dari server
    const img = document.getElementById('qrImage');
    img.src = '';
    img.src = `/master/users/${userId}/qr`;

    // Tombol download → endpoint server langsung
    const dlBtn  = document.getElementById('qrDownloadBtn');
    dlBtn.href   = `/master/users/${userId}/qr/download`;

    new bootstrap.Modal(document.getElementById('qrModal')).show();
}

function printQr() {
    const url = `${window.location.origin}/master/users/${qrUser.id}/qr`;
    const w   = window.open('', '_blank');
    w.document.write(`<!DOCTYPE html><html><head>
        <title>QR - ${qrUser.name}</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 40px; }
            img  { display: block; margin: 0 auto 12px; width: 220px; height: 220px; }
            h3   { margin: 0 0 4px; font-size: 16px; }
            p    { margin: 0; color: #666; font-size: 12px; }
        </style>
    </head><body>
        <img src="${url}" onload="window.print()">
        <h3>${qrUser.name}</h3>
        <p>NPK: ${qrUser.npk}</p>
    </body></html>`);
    w.document.close();
}

function deleteUser(id, name) {
    Swal.fire({
        title: 'Hapus User?',
        text: `User "${name}" akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then(result => {
        if (result.isConfirmed) {
            const form = document.getElementById('deleteForm');
            form.action = `/master/users/${id}`;
            form.submit();
        }
    });
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/warehouse/resources/views/master/users/index.blade.php ENDPATH**/ ?>