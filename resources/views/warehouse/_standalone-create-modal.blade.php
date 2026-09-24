@php
    $openStandaloneCreate = $errors->standaloneCreate->any()
        || request()->boolean('buat_standalone');
@endphp

<style>
    #standalone-create-modal {
        position: fixed;
        inset: 0;
        z-index: 99999;
    }
    #standalone-create-backdrop {
        position: absolute;
        inset: 0;
        background: rgb(18 22 28 / .48);
        opacity: 0;
        transition: opacity .22s cubic-bezier(.4, 0, .2, 1);
    }
    #standalone-create-panel {
        max-height: min(90vh, 640px);
        opacity: 0;
        transform: translateY(18px) scale(.96);
        transition:
            opacity .22s cubic-bezier(.4, 0, .2, 1),
            transform .32s cubic-bezier(.16, 1, .3, 1);
        will-change: opacity, transform;
    }
    #standalone-create-modal.is-open #standalone-create-backdrop { opacity: 1; }
    #standalone-create-modal.is-open #standalone-create-panel {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    @media (prefers-reduced-motion: reduce) {
        #standalone-create-backdrop,
        #standalone-create-panel { transition: none; }
    }
</style>

<div id="standalone-create-modal"
    class="{{ $openStandaloneCreate ? 'is-open' : 'hidden' }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="standalone-create-title"
    data-open="{{ $openStandaloneCreate ? '1' : '0' }}">
    <div id="standalone-create-backdrop" data-standalone-create-close></div>
    <div class="flex items-start justify-center p-4 sm:items-center sm:p-6"
        style="position:relative;z-index:1;min-height:100%;">
        <div id="standalone-create-panel" class="w-full max-w-md rounded-xl bg-white shadow-lg overflow-y-auto">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-6 py-4">
                <h2 id="standalone-create-title" class="text-base font-semibold text-slate-900">Buat PR Mandiri</h2>
                <button type="button" data-standalone-create-close
                    class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    title="Tutup">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('warehouse.standalone.store') }}" class="space-y-4 p-6">
                @csrf

                <p class="text-xs text-slate-500">
                    Untuk pengadaan yang tidak berasal dari Work Order manapun — misalnya pembelian rutin gudang.
                    Setelah dibuat, kamu akan memilih item dari Master Data Item persis seperti membuat PR dari WO.
                </p>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Judul / Keperluan <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="standalone-create-title-input" value="{{ old('title') }}" required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('title', 'standaloneCreate') border-red-400 @enderror"
                        placeholder="Contoh: Pembelian ATK Kantor Q4">
                    @error('title', 'standaloneCreate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Catatan (opsional)</label>
                    <textarea name="request_note" rows="3"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('request_note', 'standaloneCreate') border-red-400 @enderror"
                        placeholder="Detail kebutuhan…">{{ old('request_note') }}</textarea>
                    @error('request_note', 'standaloneCreate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button type="submit"
                        class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                        Buat PR Mandiri
                    </button>
                    <button type="button" data-standalone-create-close class="text-sm text-slate-500 hover:text-slate-700">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('standalone-create-modal');
    if (!modal) return;

    const titleInput = document.getElementById('standalone-create-title-input');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let closeTimer = null;

    function openModal() {
        if (closeTimer) {
            clearTimeout(closeTimer);
            closeTimer = null;
        }
        const alreadyOpen = modal.classList.contains('is-open') && !modal.classList.contains('hidden');
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        if (alreadyOpen || reduceMotion) {
            modal.classList.add('is-open');
            titleInput && titleInput.focus();
            return;
        }
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                modal.classList.add('is-open');
            });
        });
        setTimeout(function () { titleInput && titleInput.focus(); }, 180);
    }

    function closeModal() {
        if (modal.classList.contains('hidden')) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        const finish = function () {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            closeTimer = null;
        };
        if (reduceMotion) {
            finish();
            return;
        }
        closeTimer = setTimeout(finish, 260);
    }

    document.querySelectorAll('.js-open-standalone-create').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            openModal();
        });
    });

    modal.querySelectorAll('[data-standalone-create-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });

    if (modal.dataset.open === '1') openModal();
})();
</script>
