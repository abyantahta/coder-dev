<?php $__env->startSection('title','Detail PR'); ?>
<?php $__env->startSection('page-title', 'PR: ' . $purchaseRequest->pr_no); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('director.purchase-requests.index')); ?>" class="text-decoration-none">Approval PR</a></li>
    <li class="breadcrumb-item active"><?php echo e($purchaseRequest->pr_no); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-file-earmark-text me-2 text-primary"></i>Info PR</span>
                <span class="badge bg-<?php echo e($purchaseRequest->getStatusBadge()); ?>"><?php echo e($purchaseRequest->getStatusLabel()); ?></span>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-3">
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;width:110px;">No. PR</td><td class="fw-bold text-primary"><?php echo e($purchaseRequest->pr_no); ?></td></tr>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Departemen</td><td><?php echo e($purchaseRequest->department?->name ?? '-'); ?></td></tr>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Diajukan GA</td><td><?php echo e($purchaseRequest->creator->name); ?></td></tr>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Tanggal</td><td><?php echo e($purchaseRequest->created_at->format('d/m/Y H:i')); ?></td></tr>
                    <?php if($purchaseRequest->notes): ?>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Catatan GA</td><td><?php echo e($purchaseRequest->notes); ?></td></tr>
                    <?php endif; ?>
                    <?php if($purchaseRequest->qad_req_no): ?>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">No. Req QAD</td><td class="fw-bold"><?php echo e($purchaseRequest->qad_req_no); ?></td></tr>
                    <?php endif; ?>
                </table>

                <?php if($purchaseRequest->qad_sync_message): ?>
                <div class="alert alert-warning py-2 mb-3" style="font-size:12px;">
                    <i class="bi bi-info-circle me-1"></i><?php echo e($purchaseRequest->qad_sync_message); ?>

                </div>
                <?php endif; ?>

                <?php if($purchaseRequest->status === 'director_denied'): ?>
                <div class="alert alert-danger py-2 mb-3">
                    <div class="fw-semibold" style="font-size:13px;"><i class="bi bi-x-circle me-1"></i>Ditolak</div>
                    <div style="font-size:12px;"><?php echo e($purchaseRequest->reject_reason); ?></div>
                </div>
                <?php endif; ?>

                <?php if($purchaseRequest->status === 'sent_to_qad'): ?>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success" onclick="showApproveModal(<?php echo e($purchaseRequest->id); ?>,'<?php echo e($purchaseRequest->pr_no); ?>')">
                        <i class="bi bi-check-lg me-1"></i>Setujui &amp; Kirim ke QAD
                    </button>
                    <button type="button" class="btn btn-outline-danger" onclick="showRejectModal(<?php echo e($purchaseRequest->id); ?>,'<?php echo e($purchaseRequest->pr_no); ?>')">
                        <i class="bi bi-x-lg me-1"></i>Tolak &amp; Kirim ke QAD
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if($attachments->count()): ?>
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-paperclip me-2 text-primary"></i>Lampiran (<?php echo e($attachments->count()); ?>)</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php $__currentLoopData = $attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="list-group-item d-flex align-items-center gap-2" style="font-size:13px;">
                        <i class="bi <?php echo e($att->getIconClass()); ?> fs-5"></i>
                        <a href="javascript:void(0)" onclick="previewAttachment(<?php echo e(Js::from(asset('storage/'.$att->file_path))); ?>, <?php echo e(Js::from($att->original_name)); ?>, <?php echo e(Js::from($att->mime_type)); ?>)" class="text-truncate flex-grow-1 text-decoration-none">
                            <?php echo e($att->original_name); ?>

                        </a>
                        <span class="text-muted" style="font-size:11px;"><?php echo e($att->getFormattedSize()); ?></span>
                    </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-list-ul me-2 text-primary"></i>Item (<?php echo e($purchaseRequest->details->count()); ?>)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Item</th><th>Qty</th><th>Breakdown Departemen</th></tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $purchaseRequest->details; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($i+1); ?></td>
                            <td>
                                <div class="fw-semibold"><?php echo e($detail->procurementItem->name); ?></div>
                                <div class="text-muted" style="font-size:11px;"><?php echo e($detail->procurementItem->item_code); ?></div>
                            </td>
                            <td class="fw-bold text-primary"><?php echo e(number_format($detail->qty_needed, 2)); ?> <?php echo e($detail->procurementItem->uom->code); ?></td>
                            <td style="font-size:11px;">
                                <?php $__currentLoopData = $detail->sources; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $src): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary me-1 mb-1">
                                        <?php echo e($src->department->name); ?>: <?php echo e(number_format($src->qty, 2)); ?>

                                    </span>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <?php if(!empty($budgetHistory)): ?>
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-graph-up me-2 text-primary"></i>Budget vs Actual — 12 Bulan Terakhir</div>
            <div class="card-body">
                <div style="position:relative;height:280px;">
                    <canvas id="budgetChart"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>


<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold text-success"><i class="bi bi-check-circle me-2"></i>Setujui PR</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveForm" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-body pt-0">
                    <label class="form-label fw-semibold">Catatan (opsional)</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Setujui &amp; Kirim ke QAD</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold text-danger"><i class="bi bi-x-circle me-2"></i>Tolak PR</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-body pt-0">
                    <p class="text-muted mb-3" style="font-size:13px;">PR akan ditolak dan dikirim ke QAD (Deny), lalu balik ke GA untuk direvisi.</p>
                    <label class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak &amp; Kirim ke QAD</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function showApproveModal(id, no) {
    document.getElementById('approveForm').action = `/director/purchase-requests/${id}/approve`;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}
function showRejectModal(id, no) {
    document.getElementById('rejectForm').action = `/director/purchase-requests/${id}/reject`;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

<?php if(!empty($budgetHistory)): ?>
const budgetChartEl = document.getElementById('budgetChart');
if (budgetChartEl) {
    const rupiah = (v) => 'Rp ' + Number(v).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    new Chart(budgetChartEl, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(collect($budgetHistory)->pluck('label'), 15, 512) ?>,
            datasets: [
                {
                    type: 'line',
                    label: 'Budget',
                    data: <?php echo json_encode(collect($budgetHistory)->pluck('budget'), 15, 512) ?>,
                    borderColor: '#2a78d6',
                    backgroundColor: '#2a78d6',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#2a78d6',
                    tension: 0,
                    fill: false,
                    order: 0,
                },
                {
                    type: 'bar',
                    label: 'Actual',
                    data: <?php echo json_encode(collect($budgetHistory)->pluck('consumed'), 15, 512) ?>,
                    backgroundColor: '#eb6834',
                    borderRadius: 4,
                    maxBarThickness: 28,
                    order: 1,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, boxHeight: 12, font: { size: 12 } } },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `${ctx.dataset.label}: ${rupiah(ctx.parsed.y)}`,
                    },
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#898781', font: { size: 11 } } },
                y: {
                    grid: { color: '#e1e0d9' },
                    ticks: { color: '#898781', font: { size: 11 }, callback: (v) => rupiah(v) },
                },
            },
        },
    });
}
<?php endif; ?>
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/warehouse/resources/views/director/purchase-requests/show.blade.php ENDPATH**/ ?>