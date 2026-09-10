@extends('layouts.app')
@section('title', 'Riwayat PR')
@section('page-title', 'Riwayat PR')

@section('content')

<a href="{{ route('warehouse.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-blue-600 mb-4">
    ← Kembali ke Dashboard
</a>

{{-- Search & filter --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <form method="GET" action="{{ route('warehouse.history') }}" class="flex flex-wrap gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari No. WO / No. PR…"
            class="flex-1 min-w-[200px] border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <select name="status" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Semua Status</option>
            <option value="pending_warehouse" {{ request('status') === 'pending_warehouse' ? 'selected' : '' }}>Menunggu Warehouse</option>
            <option value="pr_created" {{ request('status') === 'pr_created' ? 'selected' : '' }}>PR Dibuat (QAD)</option>
            <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Barang Diterima</option>
        </select>
        <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold px-4 py-2 rounded-lg">
            Cari
        </button>
        @if (request('q') || request('status'))
        <a href="{{ route('warehouse.history') }}" class="text-sm text-slate-500 hover:text-blue-600 px-3 py-2">Reset</a>
        @endif
    </form>
</div>

{{-- History table --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                    <th class="px-5 py-3">No. WO</th>
                    <th class="px-5 py-3">No. PR</th>
                    <th class="px-5 py-3">Butuh Tanggal</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Diminta Oleh</th>
                    <th class="px-5 py-3">Tgl. PR</th>
                    <th class="px-5 py-3">Diterima</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($orders as $order)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3">
                        <a href="{{ route('work-orders.show', $order->workOrder) }}" class="text-blue-600 hover:underline font-medium">
                            {{ $order->workOrder->wo_number }}
                        </a>
                        <div class="text-xs text-slate-400">{{ \Illuminate\Support\Str::limit($order->workOrder->title, 40) }}</div>
                    </td>
                    <td class="px-5 py-3 font-mono text-slate-700">{{ $order->pr_number ?: '—' }}</td>
                    <td class="px-5 py-3 text-slate-500">{{ $order->need_date?->format('d M Y') ?: '—' }}</td>
                    <td class="px-5 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ \App\Models\WoPartOrder::statusColor($order->status) }}">
                            {{ \App\Models\WoPartOrder::statusLabel($order->status) }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-slate-500">{{ $order->requestedBy?->name ?: '—' }}</td>
                    <td class="px-5 py-3 text-slate-500">{{ $order->pr_date?->format('d M Y') ?: '—' }}</td>
                    <td class="px-5 py-3 text-slate-500">{{ $order->received_at?->format('d M Y') ?: '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-8 text-center text-slate-400">
                        @if (request('q') || request('status'))
                            Tidak ada PR yang cocok dengan pencarian.
                        @else
                            Belum ada riwayat PR.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($orders->hasPages())
    <div class="px-5 py-4 border-t border-slate-100">
        {{ $orders->links() }}
    </div>
    @endif
</div>

@endsection
