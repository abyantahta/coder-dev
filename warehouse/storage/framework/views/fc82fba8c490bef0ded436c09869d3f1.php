<?php $__env->startSection('title','Approval Pengadaan'); ?>
<?php $__env->startSection('page-title','Approval Permintaan Pengadaan'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item">Pengadaan</li>
    <li class="breadcrumb-item active">Approval</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php if($pending->count()): ?>
<div class="alert alert-warning d-flex gap-2 align-items-center mb-4">
    <i class="bi bi-clock-history fs-5"></i>
    <div><strong><?php echo e($pending->count()); ?> permintaan</strong> menunggu persetujuan Anda.</div>
</div>
<?php endif; ?>


<div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-clock me-2 text-warning"></i>Menunggu Persetujuan Anda</span>
        <span class="badge bg-warning text-dark"><?php echo e($pending->count()); ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th width="30"></th><th>No. Permintaan</th><th>Pemohon</th><th>Departemen</th><th>Keperluan</th><th>Item</th><th>Total</th><th>Tahap</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $pending; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $totalAmount = $req->details->sum(fn($d) => $d->qty * ($d->item->price ?? 0)); ?>
                    <tr>
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
                        <td><?php echo e($req->department?->name ?? '-'); ?></td>
                        <td>
                            <?php echo e(Str::limit($req->purpose, 35)); ?>

                            <?php if($req->attachments->count()): ?>
                            <i class="bi bi-paperclip text-muted ms-1" title="<?php echo e($req->attachments->count()); ?> lampiran"></i>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-secondary"><?php echo e($req->details->count()); ?> item</span></td>
                        <td class="fw-semibold text-success">Rp <?php echo e(number_format($totalAmount, 0, ',', '.')); ?></td>
                        <td>
                            <span class="badge bg-<?php echo e($req->getStatusBadge()); ?>"><?php echo e($req->getStatusLabel()); ?></span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-success me-1"
                                    onclick="showApproveModal(<?php echo e($req->id); ?>,'<?php echo e($req->req_no); ?>')">
                                <i class="bi bi-check-lg me-1"></i>Setujui
                            </button>
                            <button class="btn btn-sm btn-danger"
                                    onclick="showRejectModal(<?php echo e($req->id); ?>,'<?php echo e($req->req_no); ?>')">
                                <i class="bi bi-x-lg me-1"></i>Tolak
                            </button>
                        </td>
                    </tr>
                    <tr class="collapse" id="items-<?php echo e($req->id); ?>">
                        <td></td>
                        <td colspan="8" class="bg-light">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr><th>Item</th><th>Qty</th><th>UOM</th><th>Harga Satuan</th><th>Subtotal</th><th>Budget Bulan Ini</th><th>Terpakai</th><th>Sisa</th></tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $req->details; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php $bi = $budgetInfo[$d->id] ?? null; ?>
                                    <tr class="<?php echo e($d->is_over_budget ? 'table-danger' : ''); ?>">
                                        <td>
                                            <?php echo e($d->item->name); ?> <span class="text-muted">(<?php echo e($d->item->item_code); ?>)</span>
                                            <?php if($d->is_over_budget): ?><span class="badge bg-danger ms-1">Melebihi Budget</span><?php endif; ?>
                                        </td>
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
                                        <td class="fw-bold text-success">Rp <?php echo e(number_format($totalAmount, 0, ',', '.')); ?></td>
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
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-check-circle text-success" style="font-size:28px;"></i>
                            <p class="mt-2 mb-0">Tidak ada permintaan yang menunggu</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="historyTable">
                <thead class="table-light">
                    <tr><th width="30"></th><th>No. Permintaan</th><th>No. PR (QAD)</th><th>No. PO</th><th>Pemohon</th><th>Keperluan</th><th>Tanggal</th><th>Status</th></tr>
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
                        <td>
                            <?php echo e(Str::limit($req->purpose, 40)); ?>

                            <?php if($req->attachments->count()): ?>
                            <i class="bi bi-paperclip text-muted ms-1" title="<?php echo e($req->attachments->count()); ?> lampiran"></i>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($req->req_date->format('d/m/Y')); ?></td>
                        <td><span class="badge bg-<?php echo e($req->getStatusBadge()); ?>"><?php echo e($req->getStatusLabel()); ?></span></td>
                    </tr>
                    <tr class="collapse" id="hist-items-<?php echo e($req->id); ?>">
                        <td></td>
                        <td colspan="7" class="bg-light">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr><th>Item</th><th>Qty</th><th>UOM</th><th>Harga Satuan</th><th>Subtotal</th><th>Budget Bulan Ini</th><th>Terpakai</th><th>Sisa</th></tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $req->details; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php $bi = $budgetInfo[$d->id] ?? null; ?>
                                    <tr class="<?php echo e($d->is_over_budget ? 'table-danger' : ''); ?>">
                                        <td>
                                            <?php echo e($d->item->name); ?> <span class="text-muted">(<?php echo e($d->item->item_code); ?>)</span>
                                            <?php if($d->is_over_budget): ?><span class="badge bg-danger ms-1">Melebihi Budget</span><?php endif; ?>
                                        </td>
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
                    <tr><td colspan="8" class="text-center text-muted py-3">Belum ada riwayat</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold text-success"><i class="bi bi-check-circle me-2"></i>Setujui Permintaan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveForm" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-body pt-0">
                    <p class="text-muted mb-3" style="font-size:13px;">Permintaan <strong id="approveReqNo"></strong> akan diteruskan ke level berikutnya.</p>
                    <label class="form-label fw-semibold">Catatan (opsional)</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Catatan persetujuan..."></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Setujui</button>
                </div>
            </form>
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
                    <textarea name="reason" class="form-control" rows="3" placeholder="Tuliskan alasan penolakan..." required></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i>Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function showApproveModal(id, no) {
    document.getElementById('approveReqNo').textContent = no;
    document.getElementById('approveForm').action = `/procurement/approvals/${id}/approve`;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}
function showRejectModal(id, no) {
    document.getElementById('rejectReqNo').textContent = no;
    document.getElementById('rejectForm').action = `/procurement/approvals/${id}/reject`;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/warehouse/resources/views/procurement/approvals/index.blade.php ENDPATH**/ ?>