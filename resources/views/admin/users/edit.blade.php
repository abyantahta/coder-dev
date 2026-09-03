@extends('layouts.app')
@section('title', 'Edit User')
@section('page-title', 'Edit User — ' . $user->name)

@section('content')
<div class="max-w-xl">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5" id="user-form">
            @csrf @method('PUT')
            @include('admin.users._form', ['user' => $user])
            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold px-6 py-2.5 transition text-sm">
                    Update User
                </button>
                <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
