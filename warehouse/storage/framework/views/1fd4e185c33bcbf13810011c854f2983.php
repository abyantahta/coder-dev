<?php $__env->startSection('title','Review Kebutuhan GA'); ?>
<?php $__env->startSection('page-title','Review & Agregasi Kebutuhan GA'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item">General Affair</li>
    <li class="breadcrumb-item active">Review Kebutuhan</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php if($requests->count()): ?>
<div class="alert alert-warning d-flex gap-2 align-items-center mb-4">
    <i class="bi bi-clock-history fs-5"></i>
    <div><strong><?php echo e($requests->count()); ?> permintaan</strong> dari <strong><?php echo e($byDepartment->count()); ?> departemen</strong> menunggu direview.</div>
</div>
<?php endif; ?>

<form id="bulkApproveForm" action="<?php echo e(route('ga.review.bulk-approve')); ?>" method="POST">
    <?php echo csrf_field(); ?>

    <ul class="nav nav-tabs mb-3" id="gaReviewTabs" role="tablist">
        <?php $__currentLoopData = $byDepartment; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deptName => $deptRequests): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo e($loop->first ? 'active' : ''); ?>" data-bs-toggle="tab"
                    data-bs-target="#dept-<?php echo e(Str::slug($deptName)); ?>" type="button">
                <?php echo e($deptName); ?> <span class="badge bg-secondary ms-1"><?php echo e($deptRequests->count()); ?></span>
            </button>
        </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo e($byDepartment->isEmpty() ? 'active' : ''); ?>" data-bs-toggle="tab"
                    data-bs-target="#summary-tab" type="button">
                <i class="bi bi-bar-chart-fill me-1"></i>Summary
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <?php $__currentLoopData = $byDepartment; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deptName => $deptRequests): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="tab-pane fade <?php echo e($loop->first ? 'show active' : ''); ?>" id="dept-<?php echo e(Str::slug($deptName)); ?>">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="30"><input type="checkbox" class="form-check-input dept-check-all" data-dept="<?php echo e(Str::slug($deptName)); ?>"></th>
                                    <th width="30"></th>
                                    <th>No. Permintaan</th>
                                    <th>Pemohon</th>
                                    <th>Keperluan</th>
                                    <th>Item</th>
                                    <th>Total</th>
                                    <th width="150">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $deptRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php $reqTotal = $req->details->sum(fn($d) => $d->qty * ($d->item->price ?? 0)); ?>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input dept-check-<?php echo e(Str::slug($deptName)); ?>" name="request_ids[]" value="<?php echo e($req->id); ?>"></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#items-<?php echo e($req->id); ?>">
                                            <i class="bi bi-chevron-down"></i>
                                        </button>
                                    </td>
                                    <td><span class="fw-semibold text-primary"><?php echo e($req->req_no); ?></span></td>
                                    <td>
                                        <div class="fw-semibold" style="font-size:13px;"><?php echo e($req->user->name); ?></div>
                                        <div class="text-muted" style="font-size:11px;"><?php echo e($req->user->npk); ?></div>
                                    </td>
                                    <td style="font-size:13px;">
                                        <?php echo e(Str::limit($req->purpose, 30)); ?>

                                        <?php if($req->attachments->count()): ?>
                                        <i class="bi bi-paperclip text-muted ms-1" title="<?php echo e($req->attachments->count()); ?> lampiran"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-secondary"><?php echo e($req->details->count()); ?> item</span></td>
                                    <td class="fw-semibold text-success">Rp <?php echo e(number_format($reqTotal, 0, ',', '.')); ?></td>
                                    <td>
                                        <button type="submit" class="btn btn-sm btn-success me-1" formaction="<?php echo e(route('ga.review.approve', $req->id)); ?>">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="showRejectModal(<?php echo e($req->id); ?>,'<?php echo e($req->req_no); ?>')">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="collapse" id="items-<?php echo e($req->id); ?>">
                                    <td></td>
                                    <td colspan="7" class="bg-light">
                                        <table class="table table-sm mb-0">
                                            <thead><tr><th>Item</th><th>Qty</th><th>UOM</th><th>Harga Satuan</th><th>Subtotal</th><th>Budget Bulan Ini</th><th>Terpakai</th><th>Sisa</th><th>Catatan</th></tr></thead>
                                            <tbody>
                                                <?php $__currentLoopData = $req->details; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php $bi = $budgetInfo[$d->id] ?? null; ?>
                                                <tr>
                                                    <td><?php echo e($d->item->name); ?> <span class="text-muted">(<?php echo e($d->item->item_code); ?>)</span></td>
                                                    <td><?php echo e(number_format($d->qty, 2)); ?></td>
                                                    <td><?php echo e($d->uom->code); ?></td>
                                                    <td>Rp <?php echo e(number_format($d->item->price ?? 0, 0, ',', '.')); ?></td>
                                                    <td class="fw-semibold">Rp <?php echo e(number_format($d->qty * ($d->item->price ?? 0), 0, ',', '.')); ?></td>
                                                    <?php if($bi): ?>
                                                    <td>Rp <?php echo e(number_format($bi['budget'], 0, ',', '.')); ?></td>
                                                    <td>Rp <?php echo e(number_format($bi['consumed'], 0, ',', '.')); ?></td>
                                                    <td class="fw-semibold <?php echo e($bi['remaining'] < 0 ? 'text-danger' : 'text-success'); ?>">Rp <?php echo e(number_format($bi['remaining'], 0, ',', '.')); ?></td>
                                                    <?php else: ?>
                                                    <td colspan="3" class="text-muted">-</td>
                                                    <?php endif; ?>
                                                    <td class="text-muted"><?php echo e($d->notes ?: '-'); ?></td>
                                                </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="4" class="text-end fw-bold">Total</td>
                                                    <td class="fw-bold text-success">Rp <?php echo e(number_format($reqTotal, 0, ',', '.')); ?></td>
                                                    <td colspan="4"></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                        <?php if($req->attachments->count()): ?>
                                        <div class="mt-2 pt-2 border-top">
                                            <div class="fw-semibold mb-1" style="font-size:12px;"><i class="bi bi-paperclip me-1"></i>Lampiran (<?php echo e($req->attachments->count()); ?>)</div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php $__currentLoopData = $req->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <a href="javascript:void(0)" onclick="previewAttachment(<?php echo e(Js::from(asset('storage/'.$att->file_path))); ?>, <?php echo e(Js::from($att->original_name)); ?>, <?php echo e(Js::from($att->mime_type)); ?>)"
                                                   class="d-flex align-items-center gap-1 px-2 py-1 bg-white border rounded text-decoration-none"
                                                   style="font-size:12px;" title="<?php echo e($att->original_name); ?>">
                                                    <i class="bi <?php echo e($att->getIconClass()); ?>"></i>
                                                    <span class="text-truncate" style="max-width:140px;"><?php echo e($att->original_name); ?></span>
                                                </a>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <div class="tab-pane fade <?php echo e($byDepartment->isEmpty() ? 'show active' : ''); ?>" id="summary-tab">
            <div class="card">
                <div class="card-header"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Akumulasi Per Produk (Semua Departemen)</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Item</th><th>Kategori</th><th>Total Qty Diminta</th><th>Jumlah Request</th><th>Harga Satuan</th><th>Subtotal</th></tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $summary; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo e($row->item->name); ?></div>
                                        <div class="text-muted" style="font-size:11px;"><?php echo e($row->item->item_code); ?></div>
                                    </td>
                                    <td><?php echo e($row->item->category?->name ?? '-'); ?></td>
                                    <td class="fw-bold text-primary"><?php echo e(number_format($row->qty, 2)); ?></td>
                                    <td><?php echo e($row->count); ?></td>
                                    <td>Rp <?php echo e(number_format($row->item->price ?? 0, 0, ',', '.')); ?></td>
                                    <td class="fw-semibold text-success">Rp <?php echo e(number_format($row->qty * ($row->item->price ?? 0), 0, ',', '.')); ?></td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada permintaan menunggu review</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <?php if($summary->count()): ?>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Grand Total</td>
                                    <td class="fw-bold text-success">Rp <?php echo e(number_format($summary->sum(fn($row) => $row->qty * ($row->item->price ?? 0)), 0, ',', '.')); ?></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <label class="form-label fw-semibold">Catatan Agregasi (opsional)</label>
        <textarea name="notes" class="form-control mb-2" rows="2" placeholder="Catatan untuk PR gabungan ini..."></textarea>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Permintaan yang dicentang akan digabung jadi PR per departemen dan LANGSUNG dikirim ke QAD. Lanjutkan?')">
            <i class="bi bi-cloud-upload me-1"></i>Approve &amp; Kirim ke QAD
        </button>
    </div>
</form>

<div class="card mt-3">
    <div class="card-header"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="30"></th>
                        <th>No. Permintaan</th>
                        <th>No. PR (QAD)</th>
                        <th>No. PO</th>
                        <th>Pemohon</th>
                        <th>Departemen</th>
                        <th>Keperluan</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $history; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $histTotal = $req->details->sum(fn($d) => $d->qty * ($d->item->price ?? 0)); ?>
                    <tr>
                        <td>
                            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#hist-items-<?php echo e($req->id); ?>">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </td>
                        <td><a href="<?php echo e(route('procurement.requests.show', $req->id)); ?>" class="fw-semibold text-primary text-decoration-none"><?php echo e($req->req_no); ?></a></td>
                        <td><?php echo e($req->aggregatedPr->qad_req_no ?? '-'); ?></td>
                        <td><?php echo e($req->aggregatedPr->qad_po_no ?? '-'); ?></td>
                        <td><?php echo e($req->user->name); ?></td>
                        <td><?php echo e($req->department?->name ?? '-'); ?></td>
                        <td>
                            <?php echo e(Str::limit($req->purpose, 30)); ?>

                            <?php if($req->attachments->count()): ?>
                            <i class="bi bi-paperclip text-muted ms-1" title="<?php echo e($req->attachments->count()); ?> lampiran"></i>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($req->req_date->format('d/m/Y')); ?></td>
                        <td><span class="badge bg-<?php echo e($req->getStatusBadge()); ?>"><?php echo e($req->getStatusLabel()); ?></span></td>
                    </tr>
                    <tr class="collapse" id="hist-items-<?php echo e($req->id); ?>">
                        <td></td>
                        <td colspan="8" class="bg-light">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr><th>Item</th><th>Qty</th><th>UOM</th><th>Harga Satuan</th><th>Subtotal</th><th>Budget Bulan Ini</th><th>Terpakai</th><th>Sisa</th></tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $req->details; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php $bi = $budgetInfo[$d->id] ?? null; ?>
                                    <tr>
                                        <td><?php echo e($d->item->name); ?> <span class="text-muted">(<?php echo e($d->item->item_code); ?>)</span></td>
                                        <td><?php echo e(number_format($d->qty, 2)); ?></td>
                                        <td><?php echo e($d->uom->code); ?></td>
                                        <td>Rp <?php echo e(number_format($d->item->price ?? 0, 0, ',', '.')); ?></td>
                                        <td class="fw-semibold">Rp <?php echo e(number_format($d->qty * ($d->item->price ?? 0), 0, ',', '.')); ?></td>
                                        <?php if($bi): ?>
                                        <td>Rp <?php echo e(number_format($bi['budget'], 0, ',', '.')); ?></td>
                                        <td>Rp <?php echo e(number_format($bi['consumed'], 0, ',', '.')); ?></td>
                                        <td class="fw-semibold <?php echo e($bi['remaining'] < 0 ? 'text-danger' : 'text-success'); ?>">Rp <?php echo e(number_format($bi['remaining'], 0, ',', '.')); ?></td>
                                        <?php else: ?>
                                        <td colspan="3" class="text-muted">-</td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">Total</td>
                                        <td class="fw-bold text-success">Rp <?php echo e(number_format($histTotal, 0, ',', '.')); ?></td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table>
                            <?php if($req->attachments->count()): ?>
                            <div class="mt-2 pt-2 border-top">
                                <div class="fw-semibold mb-1" style="font-size:12px;"><i class="bi bi-paperclip me-1"></i>Lampiran (<?php echo e($req->attachments->count()); ?>)</div>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php $__currentLoopData = $req->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <a href="javascript:void(0)" onclick="previewAttachment(<?php echo e(Js::from(asset('storage/'.$att->file_path))); ?>, <?php echo e(Js::from($att->original_name)); ?>, <?php echo e(Js::from($att->mime_type)); ?>)"
                                       class="d-flex align-items-center gap-1 px-2 py-1 bg-white border rounded text-decoration-none"
                                       style="font-size:12px;" title="<?php echo e($att->original_name); ?>">
                                        <i class="bi <?php echo e($att->getIconClass()); ?>"></i>
                                        <span class="text-truncate" style="max-width:140px;"><?php echo e($att->original_name); ?></span>
                                    </a>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Belum ada riwayat</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold text-danger"><i class="bi bi-x-circle me-2"></i>Tolak Permintaan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-body pt-0">
                    <p class="text-muted mb-3" style="font-size:13px;">Permintaan <strong id="rejectReqNo"></strong> akan ditolak.</p>
                    <label class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.querySelectorAll('.dept-check-all').forEach(el => {
    el.addEventListener('change', function () {
        document.querySelectorAll('.dept-check-' + this.dataset.dept).forEach(c => c.checked = this.checked);
    });
});

function showRejectModal(id, no) {
    document.getElementById('rejectReqNo').textContent = no;
    document.getElementById('rejectForm').action = `/ga/review/${id}/reject`;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/warehouse/resources/views/procurement/ga/review/index.blade.php ENDPATH**/ ?>