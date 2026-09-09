<?php $__env->startSection('title','Setting URL QAD'); ?>
<?php $__env->startSection('page-title','Setting URL QAD'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item">Pengaturan</li>
    <li class="breadcrumb-item active">URL QAD</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-hdd-network me-2 text-primary"></i>URL Koneksi QAD</div>
            <div class="card-body">
                <form action="<?php echo e(route('admin.qad-settings.update')); ?>" method="POST">
                    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">URL QXI (QdocWebService)</label>
                        <input type="text" name="qad_soap_url" class="form-control <?php $__errorArgs = ['qad_soap_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                               value="<?php echo e(old('qad_soap_url', $setting->qad_soap_url)); ?>"
                               placeholder="<?php echo e(config('services.qad_soap.url')); ?>">
                        <?php $__errorArgs = ['qad_soap_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <div class="form-text" style="font-size:11px;">
                            Dipakai untuk kirim PR, approval, dan penerimaan PO ke QAD.
                            Kosongkan untuk pakai default dari server: <code><?php echo e(config('services.qad_soap.url')); ?></code>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">URL WSA (Web Service Adapter)</label>
                        <input type="text" name="qad_wsa_url" class="form-control <?php $__errorArgs = ['qad_wsa_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                               value="<?php echo e(old('qad_wsa_url', $setting->qad_wsa_url)); ?>"
                               placeholder="<?php echo e(config('services.qad_soap.wsa_url')); ?>">
                        <?php $__errorArgs = ['qad_wsa_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <div class="form-text" style="font-size:11px;">
                            Dipakai untuk auto-detect No. PO dari requisition (SDI_getPRtoPO_).
                            Kosongkan untuk pakai default dari server: <code><?php echo e(config('services.qad_soap.wsa_url')); ?></code>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Simpan
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="alert alert-warning mb-0" style="font-size:13px;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Ganti URL ini kalau host/port server QAD berubah (misal port <code>24079</code>).
            Username/password QAD tetap dikelola lewat konfigurasi server (<code>.env</code>), tidak lewat sini.
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/warehouse/resources/views/admin/qad-settings/edit.blade.php ENDPATH**/ ?>