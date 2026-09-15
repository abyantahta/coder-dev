<div class="flex items-center gap-3 mb-2 {{ $mt ?? '' }}">
    <h2 class="text-sm font-semibold text-slate-700">{{ $title }}</h2>
    <span class="text-xs px-2 py-0.5 rounded-full {{ $badgeClass ?? 'bg-blue-100 text-blue-700' }}">{{ $wos->total() }} WO</span>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden {{ $wrapperClass ?? 'mb-6' }}">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">No. WO</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Judul</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Requester</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Prioritas</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Status</th>
                    @if ($showAction ?? false)
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Aksi kamu</th>
                    @endif
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Assigned</th>
                    @if ($showScore ?? false)
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">SR</th>
                    @endif
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Deadline</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Dibuat</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($wos as $wo)
                @php
                    $rowAccent = $wo->isOverdue()
                        ? 'bg-red-50/50 shadow-[inset_3px_0_0_0_var(--color-red-500)]'
                        : (($accent ?? null) === 'inbox' ? 'bg-orange-50/40 shadow-[inset_3px_0_0_0_var(--color-ember)]' : '');
                @endphp
                <tr class="hover:bg-slate-50 transition cursor-pointer {{ $rowAccent }}"
                    onclick="window.location='{{ route('work-orders.show', $wo) }}'"
                    title="{{ $wo->title }}">
                    <td class="px-4 py-3 font-mono text-xs text-slate-600 whitespace-nowrap">{{ $wo->wo_number }}</td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-800 max-w-48 truncate">{{ $wo->title }}</div>
                        @if ($wo->unit) <div class="text-xs text-slate-400">{{ $wo->unit->name }}</div> @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="text-slate-700">{{ $wo->requester->name }}</div>
                        <div class="text-xs text-slate-400">{{ $wo->requester->department }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ \App\Models\WorkOrder::priorityColor($wo->priority) }}">
                            {{ ucfirst($wo->priority) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ \App\Models\WorkOrder::statusColor($wo->status) }}">
                            {{ \App\Models\WorkOrder::statusLabel($wo->status) }}
                        </span>
                    </td>
                    @if ($showAction ?? false)
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="text-xs font-semibold text-ember">{{ $wo->neededActionLabel() }}</span>
                    </td>
                    @endif
                    <td class="px-4 py-3 text-slate-600 text-xs">
                        {{ $wo->assignedMember?->name ?? ($wo->assignedGroup?->name ?? '—') }}
                    </td>
                    @if ($showScore ?? false)
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if ($wo->score !== null)
                            <span class="text-sm font-semibold {{ $wo->score >= 80 ? 'text-green-600' : ($wo->score >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ $wo->score }}%
                            </span>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    @endif
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if ($wo->deadline)
                            <span class="{{ $wo->isOverdue() ? 'text-red-600 font-semibold' : 'text-slate-500' }} text-xs">
                                {{ $wo->deadline->format('d M Y, H:i') }}
                            </span>
                            @if ($wo->isOverdue())
                            <span class="ml-1 text-xs text-red-500">(Overdue)</span>
                            @endif
                        @else
                        <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-500">
                        {{ $wo->created_at?->format('d M Y, H:i') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('work-orders.show', $wo) }}"
                            class="text-blue-600 hover:text-blue-800 text-xs font-medium">{{ $ctaLabel ?? 'Detail →' }}</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ 9 + (($showAction ?? false) ? 1 : 0) + (($showScore ?? false) ? 1 : 0) }}" class="text-center text-slate-400 py-10">
                        {{ $empty ?? 'Tidak ada work order.' }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($wos->hasPages())
    <div class="px-4 py-3 border-t border-slate-100">
        {{ $wos->links() }}
    </div>
    @endif
</div>
