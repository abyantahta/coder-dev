@extends('layouts.app')
@section('title', 'Work Orders')
@section('page-title', 'Daftar Work Order')

@section('content')
@php
    $filterParams = array_filter([
        'search'    => request('search'),
        'status'    => request('status'),
        'date_from' => request('date_from'),
        'date_to'   => request('date_to'),
        'group_id'  => request('group_id'),
        'member_id' => request('member_id'),
    ], fn ($v) => $v !== null && $v !== '');
    $tabQuery = fn (string $tab) => array_merge($filterParams, ['tab' => $tab]);
    $monthsId = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $fmtId = function ($value) use ($monthsId) {
        try {
            $d = \Carbon\Carbon::parse($value);
            return $d->format('j') . ' ' . $monthsId[$d->month] . ' ' . $d->format('Y');
        } catch (\Throwable) {
            return '';
        }
    };
    $dateFrom = request('date_from');
    $dateTo = request('date_to');
    $dateLabel = ($dateFrom && $dateTo)
        ? $fmtId($dateFrom) . ' – ' . $fmtId($dateTo)
        : ($dateFrom ? 'Dari ' . $fmtId($dateFrom) : ($dateTo ? 'Sampai ' . $fmtId($dateTo) : ''));
    $filterControl = 'border border-slate-300 rounded-lg px-3 py-2 text-sm h-10 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500';
    $tabBase = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition';
@endphp

<div class="flex items-center justify-between gap-3 mb-4">
    <div class="inline-flex items-center gap-1 rounded-xl bg-slate-100 p-1">
        <a href="{{ route('work-orders.index', $tabQuery('inbox')) }}"
            class="{{ $tabBase }}
                {{ $tab === 'inbox'
                    ? 'bg-ember text-white shadow-sm'
                    : ($inboxCount > 0 ? 'text-ember hover:bg-white' : 'text-slate-600 hover:bg-white') }}">
            Perlu Tindakan
            <span class="min-w-[1.25rem] h-5 px-1.5 rounded-full text-[11px] font-bold flex items-center justify-center
                {{ $tab === 'inbox' ? 'bg-white/20 text-white' : ($inboxCount > 0 ? 'bg-ember text-white' : 'bg-slate-200 text-slate-600') }}">
                {{ $inboxCount }}
            </span>
        </a>
        <a href="{{ route('work-orders.index', $tabQuery('progress')) }}"
            class="{{ $tabBase }} {{ $tab === 'progress' ? 'bg-ink text-white shadow-sm' : 'text-slate-600 hover:bg-white' }}">
            On Progress
            <span class="min-w-[1.25rem] h-5 px-1.5 rounded-full text-[11px] font-bold flex items-center justify-center
                {{ $tab === 'progress' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600' }}">
                {{ $progressCount }}
            </span>
        </a>
        <a href="{{ route('work-orders.index', $tabQuery('history')) }}"
            class="{{ $tabBase }} {{ $tab === 'history' ? 'bg-ink text-white shadow-sm' : 'text-slate-600 hover:bg-white' }}">
            Riwayat
            <span class="min-w-[1.25rem] h-5 px-1.5 rounded-full text-[11px] font-bold flex items-center justify-center
                {{ $tab === 'history' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600' }}">
                {{ $historyCount }}
            </span>
        </a>
    </div>
    <a href="{{ route('work-orders.create') }}"
        class="js-open-wo-create inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 h-10 rounded-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Buat WO
    </a>
</div>

<form method="GET" id="wo-filter-form" class="bg-white rounded-xl shadow-sm px-4 py-3 mb-4"
      data-groups='@json($filterGroups)'>
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input type="hidden" name="date_from" id="date_from" value="{{ $dateFrom }}">
    <input type="hidden" name="date_to" id="date_to" value="{{ $dateTo }}">

    <div class="flex items-end justify-between gap-4">
        <div class="flex flex-wrap items-end gap-3">

            <div class="relative" id="date-range-wrap" style="width:220px;min-width:220px;max-width:220px;flex-shrink:0;">
                <label class="block text-xs font-medium text-slate-600 mb-1">Tanggal dibuat</label>
                <button type="button" id="date-range-toggle"
                    style="width:220px;min-width:220px;max-width:220px;box-sizing:border-box;"
                    class="{{ $filterControl }} text-left inline-flex items-center gap-2 justify-start overflow-hidden">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span id="date-range-label" class="block min-w-0 flex-1 truncate {{ $dateLabel ? 'text-slate-800' : 'text-slate-400' }}">
                        {{ $dateLabel ?: 'Pilih rentang tanggal' }}
                    </span>
                </button>
                <div id="date-range-panel" class="hidden absolute z-30 mt-1 left-0 bg-white rounded-xl shadow-lg border border-slate-200 p-3"
                    style="width:220px;min-width:220px;box-sizing:border-box;">
                    <div class="flex items-center justify-between mb-2 gap-2">
                        <button type="button" id="cal-prev" class="p-1 rounded-lg hover:bg-slate-100 text-slate-600 shrink-0">‹</button>
                        <div id="cal-title" class="text-sm font-semibold text-slate-800 whitespace-nowrap"></div>
                        <button type="button" id="cal-next" class="p-1 rounded-lg hover:bg-slate-100 text-slate-600 shrink-0">›</button>
                    </div>
                    <div id="cal-grid" class="grid grid-cols-7 gap-px"></div>
                    <p id="cal-hint" class="text-[11px] text-slate-400 mt-2">Pilih tanggal mulai, lalu tanggal selesai.</p>
                    <div class="flex items-center justify-end mt-2 pt-2 border-t border-slate-100">
                        <button type="button" id="cal-clear" class="text-xs text-slate-500 hover:text-slate-800">Hapus tanggal</button>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Group Head</label>
                <select name="group_id" id="filter-group" class="{{ $filterControl }} w-44">
                    <option value="">Semua Group Head</option>
                    @foreach ($filterGroups as $group)
                        <option value="{{ $group['id'] }}" {{ (string) request('group_id') === (string) $group['id'] ? 'selected' : '' }}>
                            {{ $group['head_name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Member</label>
                <select name="member_id" id="filter-member" class="{{ $filterControl }} w-44">
                    <option value="">Semua Member</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                <select name="status" id="filter-status" class="{{ $filterControl }} w-44">
                    <option value="">Semua Status</option>
                    @foreach (['pending','accepted','rejected','forwarded_ga','forwarded_qa','pending_parts','parts_ordered','parts_received','assigned_group','assigned_member','completed','rework','finished','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                        {{ \App\Models\WorkOrder::statusLabel($s) }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="shrink-0">
            <label class="block text-xs font-medium text-slate-600 mb-1">Cari</label>
            <input type="text" name="search" id="filter-search" value="{{ request('search') }}"
                placeholder="Judul / No. WO…"
                class="{{ $filterControl }} w-56">
        </div>
    </div>
</form>

@include('work-orders._table', [
    'wos' => $wos,
    'title' => match ($tab) {
        'inbox'   => 'Perlu Tindakan',
        'history' => 'Riwayat (Selesai)',
        default   => 'On Progress',
    },
    'empty' => match ($tab) {
        'inbox'   => 'Tidak ada work order yang menunggu tindakanmu.',
        'history' => 'Belum ada work order yang selesai.',
        default   => 'Tidak ada work order yang sedang dikerjakan.',
    },
    'badgeClass' => match ($tab) {
        'inbox'   => 'bg-orange-100 text-orange-800',
        'history' => 'bg-slate-100 text-slate-600',
        default   => 'bg-blue-100 text-blue-700',
    },
    'showAction' => $tab === 'inbox',
    'showScore' => $tab === 'history',
    'ctaLabel' => $tab === 'inbox' ? 'Tindak lanjuti →' : 'Detail →',
    'accent' => $tab === 'inbox' ? 'inbox' : null,
])

@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('wo-filter-form');
    if (!form) return;

    const groups = JSON.parse(form.dataset.groups || '[]');
    const groupSelect = document.getElementById('filter-group');
    const memberSelect = document.getElementById('filter-member');
    const selectedMember = @json((string) request('member_id'));

    function membersForGroup(groupId) {
        if (!groupId) {
            const seen = new Set();
            const all = [];
            groups.forEach(function (g) {
                (g.members || []).forEach(function (m) {
                    if (!seen.has(m.id)) {
                        seen.add(m.id);
                        all.push(m);
                    }
                });
            });
            return all;
        }
        const group = groups.find(function (g) { return String(g.id) === String(groupId); });
        return group ? (group.members || []) : [];
    }

    function renderMembers(keepId) {
        const members = membersForGroup(groupSelect.value);
        const current = keepId || '';
        memberSelect.innerHTML = '<option value="">Semua Member</option>';
        members.forEach(function (m) {
            const opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = m.name;
            if (String(m.id) === String(current)) opt.selected = true;
            memberSelect.appendChild(opt);
        });
        if (current && !members.some(function (m) { return String(m.id) === String(current); })) {
            memberSelect.value = '';
        }
    }

    function applyFilters() {
        fromInput.value = pickStart || '';
        toInput.value = pickEnd || pickStart || '';
        Array.from(form.elements).forEach(function (el) {
            if (!el.name || el.name === 'tab') return;
            if (!el.value) el.disabled = true;
        });
        form.submit();
    }

    groupSelect.addEventListener('change', function () {
        renderMembers('');
        applyFilters();
    });
    memberSelect.addEventListener('change', applyFilters);
    document.getElementById('filter-status').addEventListener('change', applyFilters);

    let searchTimer = null;
    document.getElementById('filter-search').addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(applyFilters, 400);
    });
    renderMembers(selectedMember);

    const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const monthsShort = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const fromInput = document.getElementById('date_from');
    const toInput = document.getElementById('date_to');
    const toggle = document.getElementById('date-range-toggle');
    const panel = document.getElementById('date-range-panel');
    const grid = document.getElementById('cal-grid');
    const title = document.getElementById('cal-title');
    const label = document.getElementById('date-range-label');
    const hint = document.getElementById('cal-hint');
    let view = (fromInput.value ? new Date(fromInput.value + 'T00:00:00') : new Date());
    view = new Date(view.getFullYear(), view.getMonth(), 1);
    let pickStart = fromInput.value || null;
    let pickEnd = toInput.value || null;

    function ymd(d) {
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return d.getFullYear() + '-' + m + '-' + day;
    }
    function parseYmd(s) {
        if (!s) return null;
        const p = s.split('-');
        return new Date(Number(p[0]), Number(p[1]) - 1, Number(p[2]));
    }
    function fmtId(s) {
        const d = parseYmd(s);
        if (!d) return '';
        return d.getDate() + ' ' + monthsShort[d.getMonth()] + ' ' + d.getFullYear();
    }
    function syncLabel() {
        if (pickStart && pickEnd) {
            label.textContent = fmtId(pickStart) + ' – ' + fmtId(pickEnd);
            label.classList.remove('text-slate-400');
            label.classList.add('text-slate-800');
        } else if (pickStart) {
            label.textContent = 'Dari ' + fmtId(pickStart);
            label.classList.remove('text-slate-400');
            label.classList.add('text-slate-800');
            if (hint) hint.textContent = 'Sekarang pilih tanggal selesai.';
        } else {
            label.textContent = 'Pilih rentang tanggal';
            label.classList.add('text-slate-400');
            label.classList.remove('text-slate-800');
            if (hint) hint.textContent = 'Pilih tanggal mulai, lalu tanggal selesai.';
        }
        if (pickStart && pickEnd && hint) {
            hint.textContent = 'Pilih tanggal mulai, lalu tanggal selesai.';
        }
    }
    function inRange(s) {
        if (!pickStart || !pickEnd) return false;
        return s >= pickStart && s <= pickEnd;
    }
    function renderCal() {
        title.textContent = months[view.getMonth()] + ' ' + view.getFullYear();
        grid.innerHTML = '';
        const first = new Date(view.getFullYear(), view.getMonth(), 1);
        let startPad = first.getDay() - 1;
        if (startPad < 0) startPad = 6;
        const daysInMonth = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();
        for (let i = 0; i < startPad; i++) {
            const empty = document.createElement('div');
            empty.className = 'h-8';
            grid.appendChild(empty);
        }
        for (let day = 1; day <= daysInMonth; day++) {
            const d = new Date(view.getFullYear(), view.getMonth(), day);
            const s = ymd(d);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = day;
            btn.dataset.date = s;
            let cls = 'h-8 text-xs rounded-lg hover:bg-slate-100';
            if (s === pickStart || s === pickEnd) cls = 'h-8 text-xs rounded-lg bg-ember text-white font-semibold';
            else if (inRange(s)) cls = 'h-8 text-xs rounded-lg bg-orange-50 text-ember';
            btn.className = cls;
            btn.addEventListener('pointerdown', function (e) { e.stopPropagation(); });
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                pick(s);
            });
            grid.appendChild(btn);
        }
    }
    function pick(s) {
        if (!pickStart || (pickStart && pickEnd)) {
            pickStart = s;
            pickEnd = null;
        } else if (s < pickStart) {
            pickEnd = pickStart;
            pickStart = s;
        } else {
            pickEnd = s;
        }
        renderCal();
        syncLabel();
        if (pickStart && pickEnd) {
            panel.classList.add('hidden');
            applyFilters();
        }
    }

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        panel.classList.toggle('hidden');
        renderCal();
    });
    document.getElementById('cal-prev').addEventListener('click', function (e) {
        e.stopPropagation();
        view.setMonth(view.getMonth() - 1);
        renderCal();
    });
    document.getElementById('cal-next').addEventListener('click', function (e) {
        e.stopPropagation();
        view.setMonth(view.getMonth() + 1);
        renderCal();
    });
    document.getElementById('cal-clear').addEventListener('click', function (e) {
        e.stopPropagation();
        pickStart = null;
        pickEnd = null;
        fromInput.value = '';
        toInput.value = '';
        syncLabel();
        applyFilters();
    });
    panel.addEventListener('pointerdown', function (e) { e.stopPropagation(); });
    document.addEventListener('pointerdown', function (e) {
        if (!document.getElementById('date-range-wrap').contains(e.target)) {
            panel.classList.add('hidden');
        }
    });
})();
</script>
@endpush
