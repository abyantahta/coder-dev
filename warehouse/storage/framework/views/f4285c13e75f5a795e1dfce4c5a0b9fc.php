<?php $__env->startSection('title','Terima Barang'); ?>
<?php $__env->startSection('page-title', 'Terima Barang: ' . $purchaseRequest->pr_no); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('ga.receiving.index')); ?>" class="text-decoration-none">Terima Barang</a></li>
    <li class="breadcrumb-item active"><?php echo e($purchaseRequest->pr_no); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<form action="<?php echo e(route('ga.receiving.store', $purchaseRequest->id)); ?>" method="POST">
    <?php echo csrf_field(); ?>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label fw-semibold">No. PO (QAD) <span class="text-danger">*</span></label>
            <?php if($purchaseRequest->qad_po_no): ?>
                <input type="text" class="form-control" value="<?php echo e($purchaseRequest->qad_po_no); ?>" readonly>
                <input type="hidden" name="qad_po_no" value="<?php echo e($purchaseRequest->qad_po_no); ?>">
                <div class="form-text text-success" style="font-size:11px;">Otomatis terdeteksi dari QAD.</div>
            <?php else: ?>
                <input type="text" name="qad_po_no" class="form-control <?php $__errorArgs = ['qad_po_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       value="<?php echo e(old('qad_po_no')); ?>" placeholder="PO026752" required>
                <?php $__errorArgs = ['qad_po_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <div class="form-text text-warning" style="font-size:11px;">Belum terdeteksi otomatis dari QAD — isi manual dengan hati-hati, pastikan PO ini benar milik PR ini.</div>
            <?php endif; ?>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">No. Receipt QAD (opsional)</label>
            <input type="text" name="qad_receipt_no" class="form-control" placeholder="RCV-xxxx">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Catatan</label>
            <input type="text" name="notes" class="form-control" value="<?php echo e(old('notes', $defaultNotes)); ?>" placeholder="Catatan penerimaan...">
            <div class="form-text" style="font-size:11px;">Otomatis terisi sesuai departemen — bisa diedit.</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="bi bi-list-ul me-2 text-primary"></i>Konfirmasi Qty Diterima</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Diminta</th>
                            <th>Sudah Diterima</th>
                            <th width="150">Qty Diterima (sekarang)</th>
                            <th>Breakdown Departemen (proporsional)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $purchaseRequest->details; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $alreadyReceived = $receivedByDetailId[$detail->id] ?? 0;
                            $remaining = max(0, $detail->qty_needed - $alreadyReceived);
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo e($detail->procurementItem->name); ?></div>
                                <div class="text-muted" style="font-size:11px;"><?php echo e($detail->procurementItem->item_code); ?></div>
                                <input type="hidden" name="items[<?php echo e($loop->index); ?>][detail_id]" value="<?php echo e($detail->id); ?>">
                            </td>
                            <td><?php echo e(number_format($detail->qty_needed, 2)); ?> <?php echo e($detail->procurementItem->uom->code); ?></td>
                            <td>
                                <?php if($alreadyReceived > 0): ?>
                                    <span class="text-success fw-semibold"><?php echo e(number_format($alreadyReceived, 2)); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" max="<?php echo e($remaining); ?>"
                                       name="items[<?php echo e($loop->index); ?>][qty_received]"
                                       class="form-control form-control-sm"
                                       value="<?php echo e($remaining); ?>">
                                <?php if($alreadyReceived > 0): ?>
                                    <div class="form-text text-warning" style="font-size:10px;">Sisa: <?php echo e(number_format($remaining, 2)); ?></div>
                                <?php endif; ?>
                            </td>
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
        <div class="card-footer bg-transparent d-flex gap-2">
            <button type="submit" class="btn btn-primary"
                    onclick="return confirm('Konfirmasi penerimaan barang dan alokasikan ke departemen?')">
                <i class="bi bi-check-lg me-1"></i>Konfirmasi Terima &amp; Distribusikan
            </button>
            <a href="<?php echo e(route('ga.receiving.index')); ?>" class="btn btn-outline-secondary">Batal</a>
        </div>
    </div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/warehouse/resources/views/procurement/ga/receiving/create.blade.php ENDPATH**/ ?>