@extends('layouts.app')
@section('title', 'Konfigurasi QAD — ' . $dept->name)
@section('page-title', 'Konfigurasi QAD — ' . $dept->name)

@section('content')

@if (session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
@endif

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('dept-admin.index') }}" class="text-sm text-slate-500 hover:text-slate-700">← Kembali ke Dept Admin</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-5 max-w-lg">
    <h3 class="font-semibold text-slate-800 mb-1">Kode QAD untuk PR</h3>
    <p class="text-xs text-slate-500 mb-4">
        Dipakai saat Warehouse mengirim PR departemen ini ke QAD (SDI_CreatePR).
        Site Code wajib diisi — tanpa ini, pengiriman PR akan ditolak.
    </p>

    <form method="POST" action="{{ route('dept-admin.qad-config.update') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Site Code <span class="text-red-500">*</span></label>
            <input type="text" name="site_code" required value="{{ old('site_code', $config->site_code) }}"
                class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="rqmShip / rqmSite">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Buyer Code</label>
            <input type="text" name="buyer_code" value="{{ old('buyer_code', $config->buyer_code) }}"
                class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="routeToBuyer">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Approver Code</label>
            <input type="text" name="approver_code" value="{{ old('approver_code', $config->approver_code) }}"
                class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="routeToApr">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">End User ID</label>
            <input type="text" name="end_user_id" value="{{ old('end_user_id', $config->end_user_id) }}"
                class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="rqmEndUserid — kosong = pakai Site Code">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Requester User ID</label>
            <input type="text" name="requester_userid" value="{{ old('requester_userid', $config->requester_userid) }}"
                class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="rqmRqbyUserid — kosong = pakai username QAD SOAP">
        </div>

        <button type="submit"
            class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold px-5 py-2.5 text-sm transition">
            Simpan
        </button>
    </form>
</div>

@endsection
