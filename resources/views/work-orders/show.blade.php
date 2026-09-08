@extends('layouts.app')
@section('title', $workOrder->wo_number)
@section('page-title', $workOrder->wo_number)

@section('content')
@php $isRequester = $workOrder->requester_id === $user->id; @endphp

{{-- Read-only banner --}}
@if (!$canEdit)
<div class="flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 mb-4 text-sm text-amber-800">
    <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
    </svg>
    <span><strong>Read Only</strong> — Kamu tidak memiliki aksi untuk WO ini.</span>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ── Left Column ── --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Main Info --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-start justify-between gap-4 mb-5">
                <div class="flex-1 min-w-0">
                    <h2 class="text-xl font-bold text-slate-800">{{ $workOrder->title }}</h2>
                    <div class="flex items-center gap-2 mt-2 flex-wrap">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ \App\Models\WorkOrder::statusColor($workOrder->status) }}">
                            {{ \App\Models\WorkOrder::statusLabel($workOrder->status) }}
                        </span>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ \App\Models\WorkOrder::priorityColor($workOrder->priority) }}">
                            {{ ucfirst($workOrder->priority) }}
                        </span>
                        @if ($workOrder->isOverdue())
                            <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-semibold">OVERDUE</span>
                        @endif
                        @if ($workOrder->category)
                            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $workOrder->category }}</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('work-orders.index') }}" class="text-sm text-slate-400 hover:text-slate-600 shrink-0">← Kembali</a>
            </div>

            <div class="text-slate-700 text-sm leading-relaxed mb-5 whitespace-pre-wrap">{{ $workOrder->description }}</div>

            @if ($workOrder->attachment_path)
            <div class="mb-5">
                <a href="{{ \Illuminate\Support\Facades\Storage::url($workOrder->attachment_path) }}" target="_blank"
                    class="inline-flex items-center gap-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 transition">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2a4 4 0 014-4h4M9 17H7a2 2 0 01-2-2V5a2 2 0 012-2h6.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V8M9 17v2a2 2 0 002 2h6a2 2 0 002-2v-6a2 2 0 00-2-2h-2" />
                    </svg>
                    <span class="font-medium">{{ $workOrder->attachment_name }}</span>
                    <span class="text-xs text-slate-400 uppercase">{{ $workOrder->attachment_type }}</span>
                </a>
            </div>
            @endif

            <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-5">
                <div><div class="text-xs text-slate-400 mb-0.5">Requester</div>
                    <div class="text-sm font-medium text-slate-800">{{ $workOrder->requester->name }}</div>
                    <div class="text-xs text-slate-500">{{ $workOrder->requester->department }}</div></div>
                <div><div class="text-xs text-slate-400 mb-0.5">Tujuan</div>
                    <div class="text-sm font-medium text-slate-800">{{ ucfirst($workOrder->destination) }}</div></div>
                @if ($workOrder->unit)
                <div><div class="text-xs text-slate-400 mb-0.5">Unit</div>
                    <div class="text-sm font-medium text-slate-800">{{ $workOrder->unit->name }}</div></div>
                @endif
                @if ($workOrder->assignedGroup)
                <div><div class="text-xs text-slate-400 mb-0.5">Group</div>
                    <div class="text-sm font-medium text-slate-800">{{ $workOrder->assignedGroup->name }}</div></div>
                @endif
                @if ($workOrder->assignedMember)
                <div><div class="text-xs text-slate-400 mb-0.5">Dikerjakan oleh</div>
                    <div class="text-sm font-medium text-slate-800">{{ $workOrder->assignedMember->name }}</div></div>
                @endif
                @if ($workOrder->deadline)
                <div><div class="text-xs text-slate-400 mb-0.5">Deadline</div>
                    <div class="text-sm font-medium {{ $workOrder->isOverdue() ? 'text-red-600' : 'text-slate-800' }}">
                        {{ $workOrder->deadline->format('d M Y, H:i') }}</div></div>
                @if (!in_array($workOrder->status, ['finished', 'cancelled', 'rejected']))
                <div class="col-span-2 -mt-1">
                    <div class="text-xs text-slate-400 mb-0.5">Countdown</div>
                    <div id="countdown-timer" class="text-sm font-semibold"
                         data-deadline="{{ $workOrder->deadline->toIso8601String() }}">—</div>
                </div>
                @endif
                @endif
                @if ($workOrder->rework_count > 0)
                <div><div class="text-xs text-slate-400 mb-0.5">Rework</div>
                    <div class="text-sm font-medium text-pink-700">{{ $workOrder->rework_count }}x</div>
                    @if ($workOrder->rework_deadline)
                    <div class="text-xs text-slate-500">Deadline rework: {{ $workOrder->rework_deadline->format('d M Y') }}</div>
                    @endif
                </div>
                @endif
                @if ($workOrder->score !== null)
                <div><div class="text-xs text-slate-400 mb-0.5">Skor</div>
                    <div class="text-lg font-bold {{ $workOrder->score >= 80 ? 'text-green-600' : ($workOrder->score >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                        {{ $workOrder->score }}%</div></div>
                @endif
                @if ($workOrder->forwarded_to)
                <div><div class="text-xs text-slate-400 mb-0.5">Diteruskan ke</div>
                    <div class="text-sm font-medium text-teal-700">{{ strtoupper($workOrder->forwarded_to) }}</div>
                    <div class="text-xs text-slate-500">{{ $workOrder->forward_reason }}</div></div>
                @endif
            </div>

            @if ($workOrder->rejection_reason)
            <div class="mt-4 bg-red-50 border border-red-100 rounded-lg p-4">
                <div class="text-xs text-red-500 font-semibold mb-1">Alasan Penolakan / Pembatalan</div>
                <div class="text-sm text-red-800">{{ $workOrder->rejection_reason }}</div>
            </div>
            @endif
            @if ($workOrder->review_note)
            <div class="mt-4 bg-slate-50 border border-slate-100 rounded-lg p-4">
                <div class="text-xs text-slate-500 font-semibold mb-1">Catatan Review</div>
                <div class="text-sm text-slate-700">{{ $workOrder->review_note }}</div>
            </div>
            @endif
            @if ($workOrder->completion_note)
            <div class="mt-4 bg-green-50 border border-green-100 rounded-lg p-4">
                <div class="text-xs text-green-600 font-semibold mb-1">Catatan Penyelesaian</div>
                <div class="text-sm text-green-800">{{ $workOrder->completion_note }}</div>
            </div>
            @endif
            @if ($workOrder->completion_image_path)
            <div class="mt-4">
                <div class="text-xs text-slate-500 font-semibold mb-2">Foto Penyelesaian</div>
                <a href="{{ \Illuminate\Support\Facades\Storage::url($workOrder->completion_image_path) }}" target="_blank">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($workOrder->completion_image_path) }}"
                         alt="Foto penyelesaian" class="max-h-64 rounded-lg border border-slate-200 hover:opacity-90 transition">
                </a>
            </div>
            @endif
        </div>

        {{-- Spare Parts List (if any) --}}
        @if ($workOrder->spareParts->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="font-semibold text-slate-800 mb-3">Daftar Sparepart</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="text-left text-xs font-semibold text-slate-500 px-3 py-2">Nama Part</th>
                            <th class="text-left text-xs font-semibold text-slate-500 px-3 py-2">No. Part (QAD)</th>
                            <th class="text-center text-xs font-semibold text-slate-500 px-3 py-2">Qty</th>
                            <th class="text-left text-xs font-semibold text-slate-500 px-3 py-2">Satuan</th>
                            <th class="text-center text-xs font-semibold text-slate-500 px-3 py-2">Tersedia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($workOrder->spareParts as $part)
                        <tr class="{{ !$part->is_available ? 'bg-red-50' : '' }}">
                            <td class="px-3 py-2 font-medium text-slate-800">{{ $part->part_name }}</td>
                            <td class="px-3 py-2 text-slate-500 font-mono text-xs">{{ $part->part_number ?? '—' }}</td>
                            <td class="px-3 py-2 text-center">{{ $part->quantity }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $part->unit }}</td>
                            <td class="px-3 py-2 text-center">
                                @if ($part->is_available)
                                    <span class="text-green-600">✓ Ada</span>
                                @else
                                    <span class="text-red-600 font-semibold">✗ Tidak ada</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Part Order Info (Warehouse) --}}
        @if ($workOrder->partOrder)
        @php $po = $workOrder->partOrder; @endphp
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 {{ $po->isOverdue() ? 'border-red-400' : 'border-blue-400' }}">
            <h3 class="font-semibold text-slate-800 mb-3">Status Pengadaan Parts (Warehouse-MTC)</h3>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div><span class="text-slate-500">Status:</span>
                    <span class="ml-2 font-medium px-2 py-0.5 rounded-full text-xs {{ \App\Models\WoPartOrder::statusColor($po->status) }}">
                        {{ \App\Models\WoPartOrder::statusLabel($po->status) }}</span></div>
                @if ($po->pr_number)
                <div><span class="text-slate-500">No. PR QAD:</span>
                    <span class="ml-2 font-mono font-semibold">{{ $po->pr_number }}</span></div>
                @endif
                @if ($po->pr_date)
                <div><span class="text-slate-500">Tanggal PR:</span>
                    <span class="ml-2">{{ $po->pr_date->format('d M Y') }}</span></div>
                @endif
                @if ($po->expected_arrival)
                <div><span class="text-slate-500">Est. Tiba:</span>
                    <span class="ml-2 {{ $po->isOverdue() ? 'text-red-600 font-semibold' : '' }}">
                        {{ $po->expected_arrival->format('d M Y') }}
                        @if ($po->isOverdue()) (Overdue) @endif
                    </span></div>
                @endif
                @if ($po->received_at)
                <div><span class="text-slate-500">Diterima:</span>
                    <span class="ml-2 text-green-700 font-semibold">{{ $po->received_at->format('d M Y') }}</span>
                    @if ($po->procurement_days !== null)
                    <span class="text-xs text-slate-400">({{ $po->procurement_days }} hari)</span>
                    @endif
                </div>
                @endif
                @if ($po->handledBy)
                <div><span class="text-slate-500">Dihandle:</span>
                    <span class="ml-2">{{ $po->handledBy->name }}</span></div>
                @endif
            </div>
            @if ($po->request_note)
            <div class="mt-3 bg-amber-50 rounded px-3 py-2 text-xs text-amber-700">
                <span class="font-semibold">Catatan Unit Head:</span> {{ $po->request_note }}
            </div>
            @endif
            @if ($po->lines->isNotEmpty())
            <div class="mt-3">
                <div class="text-xs font-semibold text-slate-500 mb-1">Item yang dipesan:</div>
                <ul class="text-xs text-slate-600 list-disc list-inside">
                    @foreach ($po->lines as $line)
                    <li>{{ $line->description }} — {{ $line->quantity }} {{ $line->uom }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if ($po->warehouse_note)
            <div class="mt-3 bg-slate-100 rounded px-3 py-2 text-xs text-slate-600">{{ $po->warehouse_note }}</div>
            @endif
        </div>
        @endif

        {{-- ════ ACTION CARDS ════ --}}

        {{-- Waiting for Warehouse --}}
        @if (in_array($workOrder->status, ['pending_parts', 'parts_ordered']))
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
            <h3 class="font-semibold text-amber-800 mb-1">Menunggu Sparepart</h3>
            <p class="text-sm text-amber-700">
                @if ($workOrder->status === 'parts_ordered')
                    PR sudah dibuat. Menunggu barang tiba dari supplier.
                @else
                    Permintaan parts sedang diproses. Menunggu konfirmasi.
                @endif
            </p>
            @if ($workOrder->partOrder && ($user->id === $workOrder->assigned_member_id || $user->isWarehouseMtc() || $user->isSectionHead()))
            <a href="{{ route('warehouse.orders.show', $workOrder->partOrder) }}"
                class="inline-block mt-3 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                Kelola Pemesanan Part →
            </a>
            @endif
        </div>
        @endif

        {{-- Dynamic Action Card --}}
        @if ($canAct && $currentStep)
        @php $stepType = $currentStep->step_type; @endphp
        <div class="bg-white rounded-xl shadow-sm p-6 {{ $stepType === 'spare_parts_check' ? 'border-2 border-blue-200' : ($stepType === 'requester_review' ? 'border-2 border-orange-300' : '') }}">

            {{-- STANDARD: Terima / Tolak / Teruskan --}}
            @if ($stepType === 'standard')
            <h3 class="font-semibold text-slate-800 mb-4">{{ $currentStep->name }}</h3>
            <div class="flex gap-3 mb-3 flex-wrap">
                <form method="POST" action="{{ route('approval.advance', $workOrder) }}" class="flex-1 min-w-[120px]">
                    @csrf
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                        ✓ {{ $currentStep->action_label }}
                    </button>
                </form>
                @if ($currentStep->can_reject)
                <button onclick="document.getElementById('reject-form').classList.toggle('hidden')"
                    class="flex-1 min-w-[120px] bg-red-50 hover:bg-red-100 text-red-700 font-semibold py-2.5 rounded-lg text-sm transition">
                    ✗ {{ $currentStep->reject_label ?: 'Tolak' }}
                </button>
                @endif
                @if ($currentStep->can_forward)
                <button onclick="document.getElementById('forward-form').classList.toggle('hidden')"
                    class="flex-1 min-w-[120px] bg-teal-50 hover:bg-teal-100 text-teal-700 font-semibold py-2.5 rounded-lg text-sm transition">
                    ↪ Teruskan
                </button>
                @endif
            </div>
            @if ($currentStep->can_reject)
            <div id="reject-form" class="hidden bg-slate-50 border border-slate-200 rounded-lg p-4 mt-3">
                <p class="text-sm font-medium text-red-700 mb-2">{{ $currentStep->reject_label ?: 'Tolak WO' }}</p>
                <form method="POST" action="{{ route('approval.reject', $workOrder) }}">
                    @csrf
                    <textarea name="reason" required rows="2" placeholder="Alasan penolakan…"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 mb-2"></textarea>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Konfirmasi Tolak</button>
                </form>
            </div>
            @endif
            @if ($currentStep->can_forward)
            <div id="forward-form" class="hidden bg-slate-50 border border-slate-200 rounded-lg p-4 mt-3">
                <p class="text-sm font-medium text-teal-700 mb-2">Teruskan ke Departemen Lain</p>
                <form method="POST" action="{{ route('approval.forward', $workOrder) }}">
                    @csrf
                    <select name="target_dept_id" required
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400 mb-2">
                        <option value="">— Pilih Tujuan —</option>
                        @foreach ($forwardTargets as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <textarea name="reason" required rows="2" placeholder="Alasan meneruskan…"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400 mb-2"></textarea>
                    <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Konfirmasi Teruskan</button>
                </form>
            </div>
            @endif

            {{-- SPARE PARTS CHECK --}}
            @elseif ($stepType === 'spare_parts_check')
            <h3 class="font-semibold text-slate-800 mb-1">{{ $currentStep->name }}</h3>
            <p class="text-xs text-slate-500 mb-4">Pastikan sparepart tersedia sebelum melanjutkan ke Group Head.</p>

            <div class="flex gap-3 mb-3 flex-wrap">
                <button type="button" onclick="
                    document.getElementById('assign-gh-form').classList.toggle('hidden');
                    document.getElementById('parts-unavailable-form').classList.add('hidden');
                " class="flex-1 min-w-[160px] bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                    ✓ Assign to GH
                </button>
                <button type="button" onclick="
                    document.getElementById('parts-unavailable-form').classList.toggle('hidden');
                    document.getElementById('assign-gh-form').classList.add('hidden');
                " class="flex-1 min-w-[160px] bg-red-50 hover:bg-red-100 text-red-700 font-semibold py-2.5 rounded-lg text-sm transition">
                    ✗ Sparepart Tidak Ada
                </button>
            </div>

            {{-- Form terpisah agar field required yang tersembunyi tidak memblokir submit --}}
            <form method="POST" action="{{ route('approval.advance', $workOrder) }}"
                  id="assign-gh-form" class="hidden bg-slate-50 border border-slate-200 rounded-lg p-4 mb-3">
                @csrf
                <input type="hidden" name="decision" value="assign_gh">
                <p class="text-sm font-medium text-green-700 mb-2">Assign ke Group Head</p>
                @if ($assignableGroups->isEmpty())
                <p class="text-sm text-red-600">Belum ada Group untuk unit ini — hubungi Section Head untuk membuat Group terlebih dahulu.</p>
                @else
                <select name="group_id" id="spare-check-group-select" required
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 mb-2">
                    <option value="">— Pilih Group Head —</option>
                    @foreach ($assignableGroups as $group)
                    @php $gh = $group->groupHead(); @endphp
                    <option value="{{ $group->id }}">{{ $gh ? $gh->name : $group->name.' (belum ada Group Head)' }}</option>
                    @endforeach
                </select>
                <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                    Konfirmasi Assign
                </button>
                @endif
            </form>

            <form method="POST" action="{{ route('approval.advance', $workOrder) }}"
                  id="parts-unavailable-form" class="hidden bg-slate-50 border border-slate-200 rounded-lg p-4">
                @csrf
                <input type="hidden" name="decision" value="parts_unavailable">
                <p class="text-sm font-medium text-red-700 mb-2">Catatan untuk Warehouse-MTC</p>
                <textarea name="note" rows="3" required placeholder="Contoh: Part urgent untuk mesin press line 2…"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 mb-2"></textarea>
                <button type="submit"
                    class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                    Kirim ke Warehouse-MTC
                </button>
            </form>

            {{-- ASSIGN ke GROUP --}}
            @elseif ($stepType === 'assign' && $currentStep->assigns_to_role_key === 'group_head')
            <h3 class="font-semibold text-slate-800 mb-4">{{ $currentStep->name }}</h3>
            <form method="POST" action="{{ route('approval.advance', $workOrder) }}" class="flex gap-3">
                @csrf
                <select name="group_id" required class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Pilih Group —</option>
                    @foreach ($assignableGroups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                    {{ $currentStep->action_label }} →
                </button>
            </form>

            {{-- ASSIGN ke MEMBER / STAFF --}}
            @elseif ($stepType === 'assign')
            <h3 class="font-semibold text-slate-800 mb-1">{{ $currentStep->name }}</h3>
            @if ($workOrder->leadtime_days)
            <p class="text-xs text-slate-500 mb-4">Leadtime pengerjaan <strong>{{ $workOrder->leadtime_days }} hari kerja</strong> dimulai dari saat assign.</p>
            @endif
            <form method="POST" action="{{ route('approval.advance', $workOrder) }}" class="flex gap-3">
                @csrf
                <select name="member_id" required class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Pilih {{ ucfirst(str_replace('_', ' ', $currentStep->assigns_to_role_key)) }} —</option>
                    @foreach ($assignableMembers as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                    {{ $currentStep->action_label }}
                </button>
            </form>

            {{-- MATERIAL CHECK (assigned staff checks availability themselves) --}}
            @elseif ($stepType === 'material_check')
            <h3 class="font-semibold text-slate-800 mb-1">{{ $currentStep->name }}</h3>
            <p class="text-xs text-slate-500 mb-4">Leadtime pengerjaan mulai berjalan setelah material dipastikan tersedia.</p>

            <div class="flex gap-3 flex-wrap">
                <form method="POST" action="{{ route('approval.advance', $workOrder) }}" class="flex-1 min-w-[160px]">
                    @csrf
                    <input type="hidden" name="decision" value="available">
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                        ✓ Material Tersedia
                    </button>
                </form>
                <button type="button" onclick="document.getElementById('material-unavailable-form').classList.toggle('hidden')"
                    class="flex-1 min-w-[160px] bg-red-50 hover:bg-red-100 text-red-700 font-semibold py-2.5 rounded-lg text-sm transition">
                    ✗ Material Tidak Ada
                </button>
            </div>

            <form method="POST" action="{{ route('approval.advance', $workOrder) }}"
                  id="material-unavailable-form" class="hidden bg-slate-50 border border-slate-200 rounded-lg p-4 mt-3">
                @csrf
                <input type="hidden" name="decision" value="unavailable">
                <p class="text-sm font-medium text-red-700 mb-2">Catatan Pemesanan</p>
                <textarea name="note" rows="3" required placeholder="Material apa yang perlu dipesan…"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 mb-2"></textarea>
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
                    Pesan Material
                </button>
            </form>

            {{-- COMPLETION --}}
            @elseif ($stepType === 'completion')
            <h3 class="font-semibold text-slate-800 mb-2">
                {{ $workOrder->status === 'rework' ? 'Selesaikan Rework' : $currentStep->name }}
            </h3>
            @if ($workOrder->status === 'rework')
            <div class="text-sm text-pink-700 bg-pink-50 border border-pink-100 rounded-lg p-3 mb-3">
                Rework #{{ $workOrder->rework_count }} — Catatan: {{ $workOrder->review_note }}<br>
                Deadline rework: <strong>{{ $workOrder->rework_deadline?->format('d M Y, H:i') }}</strong>
            </div>
            @endif
            <form method="POST" action="{{ route('approval.advance', $workOrder) }}"
                  enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">
                        Catatan Penyelesaian <span class="text-slate-400">(opsional)</span>
                    </label>
                    <textarea name="completion_note" rows="3"
                        placeholder="Tuliskan detail pekerjaan yang telah selesai…"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">
                        Foto Penyelesaian <span class="text-slate-400">(opsional)</span>
                    </label>
                    <input type="file" name="completion_image" accept="image/*"
                        class="text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0
                               file:text-xs file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                </div>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-2.5 rounded-lg text-sm transition">
                    {{ $workOrder->status === 'rework' ? '✓ Rework Selesai' : '✓ ' . $currentStep->action_label }}
                </button>
            </form>

            {{-- REQUESTER REVIEW --}}
            @elseif ($stepType === 'requester_review')
            <h3 class="font-semibold text-slate-800 mb-1">{{ $currentStep->name }}</h3>
            @if ($currentStep->auto_advance_hours && $workOrder->completed_at)
            @php $autoAt = $workOrder->completed_at->addHours($currentStep->auto_advance_hours); @endphp
            <div class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-3">
                ⏱ Auto-confirm dalam <strong>{{ now()->diffForHumans($autoAt, true) }}</strong>
                ({{ $autoAt->format('d M Y, H:i') }}) jika tidak direspons.
            </div>
            @endif
            <p class="text-sm text-slate-500 mb-4">Periksa hasil pekerjaan. Setujui jika OK, atau minta rework.</p>
            <form method="POST" action="{{ route('approval.advance', $workOrder) }}" class="space-y-4">
                @csrf
                <textarea name="review_note" rows="3" placeholder="Catatan review (opsional)…"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                <div class="flex gap-3">
                    <button type="submit" name="action" value="approve"
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                        ✓ {{ $currentStep->action_label }}
                    </button>
                    <button type="submit" name="action" value="rework"
                        class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                        ↺ Minta Rework
                    </button>
                </div>
            </form>
            @endif

        </div>
        @endif

        {{-- Cancel Card --}}
        @if ($canCancel)
        <div class="bg-white rounded-xl shadow-sm p-5">
            <button onclick="document.getElementById('cancel-form').classList.toggle('hidden')"
                class="text-sm text-red-500 hover:text-red-700 font-medium">Batalkan WO ini</button>
            <div id="cancel-form" class="hidden mt-3">
                <form method="POST" action="{{ route('approval.cancel', $workOrder) }}">
                    @csrf
                    <textarea name="reason" required rows="2" placeholder="Alasan pembatalan…"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 mb-2"></textarea>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Konfirmasi Batalkan</button>
                </form>
            </div>
        </div>
        @endif

    </div>

    {{-- ── Right Column: Timeline ── --}}
    <div>
        <div class="bg-white rounded-xl shadow-sm p-5 sticky top-4">
            <h3 class="font-semibold text-slate-800 mb-4">Riwayat WO</h3>
            <div class="space-y-1">
                @forelse ($workOrder->histories as $history)
                <div class="flex gap-3">
                    <div class="flex flex-col items-center">
                        <div class="w-2.5 h-2.5 rounded-full bg-blue-500 mt-1.5 shrink-0"></div>
                        @if (!$loop->last)
                        <div class="w-0.5 bg-slate-200 flex-1 mt-1"></div>
                        @endif
                    </div>
                    <div class="pb-3 min-w-0">
                        <div class="text-xs font-semibold text-slate-700 capitalize">{{ str_replace('_', ' ', $history->action) }}</div>
                        @if ($history->description)
                        <div class="text-xs text-slate-500 mt-0.5">{{ $history->description }}</div>
                        @endif
                        <div class="text-xs text-slate-400 mt-0.5">{{ $history->user->name }} · {{ $history->created_at->format('d M Y, H:i') }}</div>
                    </div>
                </div>
                @empty
                <p class="text-xs text-slate-400">Belum ada riwayat.</p>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
// Countdown timer
(function () {
    const el = document.getElementById('countdown-timer');
    if (!el) return;
    const deadline = new Date(el.dataset.deadline);

    function colorFor(diffMs) {
        if (diffMs <= 0) return '#dc2626';          // red-600
        if (diffMs < 86400000) return '#dc2626';    // <1 day red
        if (diffMs < 3 * 86400000) return '#ea580c'; // <3 days orange
        if (diffMs < 7 * 86400000) return '#ca8a04'; // <7 days yellow
        return '#16a34a';                            // green
    }

    function fmt(diffMs) {
        const sign = diffMs < 0;
        const abs  = Math.abs(diffMs);
        const d = Math.floor(abs / 86400000);
        const h = Math.floor((abs % 86400000) / 3600000);
        const m = Math.floor((abs % 3600000) / 60000);
        let txt = '';
        if (d > 0) txt += d + ' hari ';
        txt += h + ' jam ' + m + ' menit';
        return sign ? 'OVERDUE ' + txt + ' yang lalu' : txt;
    }

    function update() {
        const diff = deadline - Date.now();
        el.style.color = colorFor(diff);
        el.textContent = fmt(diff);
    }

    update();
    setInterval(update, 60000);
})();
</script>
@endpush
