@extends('layouts.app')
@section('title', 'Tambah User')
@section('page-title', 'Tambah User Baru')

@section('content')
<div class="max-w-xl">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5" id="user-form">
            @csrf
            @include('admin.users._form', ['user' => null])
            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold px-6 py-2.5 transition text-sm">
                    Simpan User
                </button>
                <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
