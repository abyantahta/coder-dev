<?php $__env->startSection('title', isset($item->id) ? 'Edit Item ATK' : 'Tambah Item ATK'); ?>
<?php $__env->startSection('page-title', isset($item->id) ? 'Edit Item ATK' : 'Tambah Item ATK'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('procurement.items.index')); ?>" class="text-decoration-none">Item ATK</a></li>
    <li class="breadcrumb-item active"><?php echo e(isset($item->id) ? 'Edit' : 'Tambah'); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-box-seam me-2 text-primary"></i><?php echo e(isset($item->id) ? 'Edit' : 'Tambah'); ?> Item ATK</div>
            <div class="card-body">
                <form action="<?php echo e(isset($item->id) ? route('procurement.items.update', $item->id) : route('procurement.items.store')); ?>"
                      method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <?php if(isset($item->id)): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Kode Item</label>
                            <?php if(isset($item->id)): ?>
                                <input type="text" class="form-control bg-light" value="<?php echo e($item->item_code); ?>" readonly>
                                <div class="form-text" style="font-size:11px;">Kode item tidak bisa diubah.</div>
                            <?php else: ?>
                                <div class="input-group">
                                    <select name="prefix" id="prefixSelect" class="form-select <?php $__errorArgs = ['prefix'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" style="max-width:110px;" onchange="updateCodePreview()">
                                        <?php $__empty_1 = true; $__currentLoopData = $prefixes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <option value="<?php echo e($p->code); ?>" <?php echo e(old('prefix') === $p->code ? 'selected' : ''); ?>><?php echo e($p->code); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <option value="">-- Belum ada --</option>
                                        <?php endif; ?>
                                    </select>
                                    <input type="text" class="form-control bg-light" id="codePreview" value="<?php echo e($nextItemCode ?? '-'); ?>" readonly>
                                </div>
                                <?php $__errorArgs = ['prefix'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                <div class="form-text" style="font-size:11px;">Pilih prefix — nomor urut otomatis lanjut sesuai prefix. Kelola daftar prefix di <a href="<?php echo e(route('procurement.item-prefixes.index')); ?>" target="_blank">Master Prefix Kode</a>.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Nama Item <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   value="<?php echo e(old('name', $item->name)); ?>" placeholder="Nama item ATK">
                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">UOM <span class="text-danger">*</span></label>
                            <select name="uom_id" class="form-select <?php $__errorArgs = ['uom_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <option value="">-- Pilih Satuan --</option>
                                <?php $__currentLoopData = $uoms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $uom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($uom->id); ?>" <?php echo e(old('uom_id', $item->uom_id) == $uom->id ? 'selected' : ''); ?>>
                                    <?php echo e($uom->code); ?> — <?php echo e($uom->name); ?>

                                </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['uom_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kategori</label>
                            <select name="category_id" class="form-select <?php $__errorArgs = ['category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <option value="">-- Tanpa Kategori --</option>
                                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($cat->id); ?>" <?php echo e(old('category_id', $item->category_id) == $cat->id ? 'selected' : ''); ?>><?php echo e($cat->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            <div class="form-text" style="font-size:11px;">Kelola daftar kategori di <a href="<?php echo e(route('procurement.categories.index')); ?>">Master Kategori Item</a>.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Harga per Satuan</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="price" min="0" step="1"
                                       class="form-control <?php $__errorArgs = ['price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('price', $item->price)); ?>" placeholder="0">
                            </div>
                            <?php $__errorArgs = ['price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            <div class="form-text" style="font-size:11px;">Estimasi harga — dipakai buat hitung budget vs actual.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Keterangan</label>
                            <textarea name="description" class="form-control" rows="2"
                                      placeholder="Spesifikasi atau keterangan tambahan"><?php echo e(old('description', $item->description)); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Foto Item</label>
                            <?php if(isset($item->id) && $item->photo): ?>
                            <div class="mb-2"><img src="<?php echo e(asset('storage/'.$item->photo)); ?>" class="img-thumbnail" style="height:70px;object-fit:cover;"></div>
                            <?php endif; ?>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
                        <?php if(isset($item->id)): ?>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
                                       <?php echo e(old('is_active', $item->is_active) ? 'checked' : ''); ?>>
                                <label class="form-check-label fw-semibold" for="isActive">Item Aktif</label>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                        <a href="<?php echo e(route('procurement.items.index')); ?>" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php if(!isset($item->id)): ?>
<?php $__env->startPush('scripts'); ?>
<script>
function updateCodePreview() {
    const prefix = document.getElementById('prefixSelect').value;
    const preview = document.getElementById('codePreview');
    if (!prefix) { preview.value = '-'; return; }

    fetch('<?php echo e(route("procurement.item-prefixes.next-code")); ?>?prefix=' + encodeURIComponent(prefix))
        .then(r => r.json())
        .then(data => { preview.value = data.code; })
        .catch(() => { preview.value = '-'; });
}
</script>
<?php $__env->stopPush(); ?>
<?php endif; ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/warehouse/resources/views/procurement/master/items/form.blade.php ENDPATH**/ ?>