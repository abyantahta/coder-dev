<?php $__env->startSection('title','Budget Kebutuhan GA'); ?>
<?php $__env->startSection('page-title','Budget per Item / Departemen — ' . now()->translatedFormat('F Y')); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item">General Affair</li>
    <li class="breadcrumb-item active">Budget</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-plus-circle me-2 text-primary"></i>Set Budget Default Baru</div>
    <div class="card-body">
        <form action="<?php echo e(route('ga.budget.store')); ?>" method="POST" class="row g-2 align-items-end">
            <?php echo csrf_field(); ?>
            <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:12px;">Departemen</label>
                <select name="department_id" class="form-select form-select-sm select2-dept" required>
                    <option value="">-- Pilih --</option>
                    <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($d->id); ?>"><?php echo e($d->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:12px;">Item</label>
                <select name="procurement_item_id" class="form-select form-select-sm select2-item" required>
                    <option value="">-- Pilih --</option>
                    <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($i->id); ?>"><?php echo e($i->name); ?> (<?php echo e($i->item_code); ?>)</option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" style="font-size:12px;">Budget Default / Bulan</label>
                <input type="number" step="0.01" min="0" name="default_budget" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-save"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-cash-coin me-2 text-primary"></i>Budget vs Actual Bulan Ini</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Departemen</th><th>Item</th><th>Default</th><th>Top-up Bulan Ini</th>
                        <th>Effective Budget</th><th>Actual</th><th>Sisa</th><th width="200"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $budgets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="<?php echo e($b->remaining < 0 ? 'table-danger' : ''); ?>">
                        <td><?php echo e($b->department->name); ?></td>
                        <td><?php echo e($b->procurementItem->name); ?> <span class="text-muted">(<?php echo e($b->procurementItem->item_code); ?>)</span></td>
                        <td>Rp <?php echo e(number_format($b->default_budget, 0, ',', '.')); ?></td>
                        <td>Rp <?php echo e(number_format($b->effective - $b->default_budget, 0, ',', '.')); ?></td>
                        <td class="fw-semibold">Rp <?php echo e(number_format($b->effective, 0, ',', '.')); ?></td>
                        <td>Rp <?php echo e(number_format($b->consumed, 0, ',', '.')); ?></td>
                        <td class="fw-bold <?php echo e($b->remaining < 0 ? 'text-danger' : 'text-success'); ?>">
                            Rp <?php echo e(number_format($b->remaining, 0, ',', '.')); ?>

                            <?php if($b->remaining < 0): ?><span class="badge bg-danger ms-1">Over Budget</span><?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#topup-<?php echo e($b->id); ?>">
                                <i class="bi bi-plus-lg"></i> Top-up
                            </button>
                        </td>
                    </tr>
                    <tr class="collapse" id="topup-<?php echo e($b->id); ?>">
                        <td colspan="8" class="bg-light">
                            <form action="<?php echo e(route('ga.budget.topup', $b->id)); ?>" method="POST" class="row g-2 align-items-end py-2">
                                <?php echo csrf_field(); ?>
                                <div class="col-md-3">
                                    <label class="form-label" style="font-size:11px;">Jumlah Top-up</label>
                                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label" style="font-size:11px;">Catatan</label>
                                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Alasan penambahan budget...">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-success btn-sm w-100">Simpan</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada budget yang diset</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
$('.select2-dept, .select2-item').select2({ theme: 'bootstrap-5' });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/warehouse/resources/views/procurement/ga/budget/index.blade.php ENDPATH**/ ?>