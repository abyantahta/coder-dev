<?php

namespace App\Http\Controllers;

use App\Models\QadItem;
use App\Models\User;
use App\Models\WoPartOrder;
use App\Models\WoPartOrderLine;
use App\Services\ApprovalService;
use App\Services\Qad\QadItemService;
use App\Services\Qad\QadRequisitionService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    // Every order for the acting user's department — WO-linked (via the
    // WO's target department) or standalone (department_id set directly).
    private function warehouseOrdersQuery()
    {
        $user = Auth::user();

        return WoPartOrder::query()->where(function ($q) use ($user) {
            $q->where('department_id', $user->department_id)
                ->orWhereHas('workOrder', fn ($q2) => $q2->where('target_department_id', $user->department_id));
        });
    }

    public function index()
    {
        $user = Auth::user();

        $pendingOrders = $this->warehouseOrdersQuery()
            ->with(['workOrder.requester', 'workOrder.spareParts', 'requestedBy', 'lines'])
            ->where('status', 'pending_warehouse')
            ->latest()
            ->get();

        $activeOrders = $this->warehouseOrdersQuery()
            ->with(['workOrder.requester', 'workOrder.spareParts', 'requestedBy', 'handledBy', 'lines'])
            ->where('status', 'pr_created')
            ->latest()
            ->get();

        $inProcessOrders = $pendingOrders->concat($activeOrders)
            ->sortByDesc('created_at')
            ->values();

        $receivedOrders = $this->warehouseOrdersQuery()
            ->with(['workOrder.requester', 'handledBy'])
            ->where('status', 'received')
            ->where('received_at', '>=', now()->subDays(30))
            ->latest('received_at')
            ->get();

        // Stats
        $stats = [
            'pending' => $pendingOrders->count(),
            'in_progress' => $activeOrders->count(),
            'received_30d' => $receivedOrders->count(),
            'overdue' => $activeOrders->filter(fn ($o) => $o->isOverdue())->count(),
        ];

        // Average procurement days (last 90 days)
        $avgDays = $this->warehouseOrdersQuery()
            ->where('status', 'received')
            ->whereNotNull('pr_date')
            ->whereNotNull('received_at')
            ->where('received_at', '>=', now()->subDays(90))
            ->get()
            ->map(fn ($o) => $o->procurement_days)
            ->filter()
            ->average();

        $stats['avg_procurement_days'] = $avgDays ? round($avgDays, 1) : null;

        // Monthly procurement trend (last 6 months)
        $monthlyTrend = $this->warehouseOrdersQuery()
            ->where('status', 'received')
            ->where('received_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw("DATE_FORMAT(received_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as total'),
                DB::raw('AVG(DATEDIFF(received_at, pr_date)) as avg_days')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Compliance rate: received within 30 days
        $allReceived = $this->warehouseOrdersQuery()
            ->where('status', 'received')->whereNotNull('pr_date')->whereNotNull('received_at')->get();
        $onTime = $allReceived->filter(fn ($o) => $o->procurement_days !== null && $o->procurement_days <= 30)->count();
        $stats['compliance_rate'] = $allReceived->count() > 0
            ? round(($onTime / $allReceived->count()) * 100, 1)
            : null;

        return view('warehouse.index', compact(
            'pendingOrders', 'activeOrders', 'receivedOrders', 'inProcessOrders', 'stats', 'monthlyTrend'
        ));
    }

    // Full historical list of every part order (not just pending/active/last-30-days)
    public function history(Request $request)
    {
        $orders = $this->warehouseOrdersQuery()
            ->with(['workOrder', 'requestedBy', 'handledBy'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = $request->q;
                $query->where(fn ($q) => $q
                    ->where('pr_number', 'like', "%{$term}%")
                    ->orWhereHas('workOrder', fn ($q2) => $q2->where('wo_number', 'like', "%{$term}%")));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('warehouse.history', compact('orders'));
    }

    // Submenu: every PR/PO record ever made for this department, as 2
    // togglable tabs (?tab=pr|po). PR tab also filters by a derived status
    // (belum disetujui / approval / sudah jadi PO / sudah closed); PO tab
    // shows every line under each PO (from our own records, not QAD's
    // active-PO browse, which drops fully-received lines) with a Receive
    // action per PO. Each tab is paginated so live QAD calls (per PR's
    // received qty) stay bounded by page size, not total history.
    public function prPoHistory(Request $request, QadRequisitionService $qad)
    {
        $tab = $request->tab === 'po' ? 'po' : 'pr';
        $user = Auth::user();

        $prOrders = null;
        $prFilter = 'all';
        $prCounts = [];
        $poRows = null;

        if ($tab === 'pr') {
            $all = $this->warehouseOrdersQuery()
                ->whereNotNull('pr_number')
                ->with(['workOrder', 'lines'])
                ->latest('pr_date')
                ->get();

            // PR "status" isn't a single column — it's derived from
            // approval_status + whether any/all lines have a PO yet + the
            // order's own status — so filtering happens in PHP after
            // eager-loading, not as a WHERE clause.
            $classify = fn ($order) => match (true) {
                $order->status === 'received' => 'closed',
                ! empty($order->poNumbers()) => 'has_po',
                $order->qad_approval_status === '2' => 'approved',
                default => 'pending',
            };

            $prCounts = ['all' => $all->count(), 'pending' => 0, 'approved' => 0, 'has_po' => 0, 'closed' => 0];
            foreach ($all as $order) {
                $prCounts[$classify($order)]++;
            }

            $prFilter = in_array($request->status, ['pending', 'approved', 'has_po', 'closed'], true) ? $request->status : 'all';
            $filtered = $prFilter === 'all' ? $all : $all->filter(fn ($order) => $classify($order) === $prFilter)->values();

            $perPage = 20;
            $page = LengthAwarePaginator::resolveCurrentPage();
            $prOrders = new LengthAwarePaginator(
                $filtered->forPage($page, $perPage)->values(),
                $filtered->count(),
                $perPage,
                $page,
                ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
            );
        } else {
            $inDept = fn ($q) => $q->where(fn ($q2) => $q2
                ->where('department_id', $user->department_id)
                ->orWhereHas('workOrder', fn ($q3) => $q3->where('target_department_id', $user->department_id)));

            $poNumbersPage = $this->paginateDistinctPoNumbers(
                fn ($q) => $q->whereHas('partOrder', $inDept),
                $request
            );

            $canReceive = $user->canReceiveInQad();

            // Every line under this PO, straight from our own records
            // (not QAD's active-PO browse, which drops fully-received
            // lines) — this is what both the "seluruh baris item" expand
            // view and the Receive popup are built from.
            $poRows = $poNumbersPage->through(function ($poNo) use ($qad, $canReceive) {
                $orders = WoPartOrder::whereHas('lines', fn ($q) => $q->where('qad_po_no', $poNo))
                    ->with(['workOrder', 'lines'])
                    ->get();

                $items = collect();
                foreach ($orders as $order) {
                    $received = $order->pr_number ? $qad->getReceivedQtyByLine($order->pr_number) : [];
                    foreach ($order->lines as $i => $line) {
                        if ($line->qad_po_no !== $poNo) {
                            continue;
                        }
                        $qtyReceived = $received[$i + 1] ?? 0.0;
                        $items->push((object) [
                            'order' => $order,
                            'line' => $line,
                            'qty_received' => $qtyReceived,
                            'is_received' => $qtyReceived >= (float) $line->quantity,
                        ]);
                    }
                }

                return (object) [
                    'po_no' => $poNo,
                    'items' => $items,
                    'fully_received' => $items->isNotEmpty() && $items->every(fn ($i) => $i->is_received),
                    'can_receive' => $canReceive,
                ];
            });
        }

        return view('warehouse.pr-po-history', compact('tab', 'prOrders', 'prFilter', 'prCounts', 'poRows'));
    }

    // Standalone PR/PO — Warehouse starts a procurement on its own, not
    // derived from any WO's material-check step. Same PR→PO→receive flow
    // as a WO-linked order after this (WoPartOrder::isStandalone()). No
    // dedicated page — this is a popup (see _standalone-create-modal.blade.php),
    // so a direct/no-JS visit just lands on the dashboard with it auto-opened.
    public function createStandalone()
    {
        return redirect()->route('warehouse.index', ['buat_standalone' => 1]);
    }

    public function storeStandalone(Request $request)
    {
        $user = Auth::user();

        // Named error bag — 'title' is also used by the WO-create modal,
        // and both modals are included globally, so a shared/default bag
        // would risk popping the wrong one open on a validation failure.
        $request->validateWithBag('standaloneCreate', [
            'title' => 'required|string|max:255',
            'request_note' => 'nullable|string|max:500',
        ]);

        $order = WoPartOrder::create([
            'department_id' => $user->department_id,
            'title' => $request->title,
            'requested_by' => $user->id,
            'request_note' => $request->request_note,
            'status' => 'pending_warehouse',
        ]);

        return redirect()->route('warehouse.orders.show', $order)
            ->with('success', 'PR mandiri dibuat — tambahkan item lalu buat PR ke QAD.');
    }

    // Warehouse — or the WO's own assigned staffer, acting as their own
    // warehouse — sends the PR to QAD. Works the same for a standalone
    // order (no parent WO) — it just has no WO status/history to update.
    public function createPr(Request $request, WoPartOrder $partOrder, QadRequisitionService $qad)
    {
        $user = Auth::user();
        abort_unless($this->canManageOrder($user, $partOrder), 403);
        abort_unless($partOrder->status === 'pending_warehouse', 422, 'Order tidak dalam status menunggu PR.');
        if ($partOrder->workOrder) {
            abort_unless($partOrder->workOrder->status === 'pending_parts', 422, 'WO tidak dalam status menunggu parts.');
        }

        $request->validate([
            'need_date' => 'required|date',
            'warehouse_note' => 'nullable|string|max:500',
        ]);

        abort_unless($partOrder->lines()->exists(), 422, 'Tambahkan minimal 1 item sparepart sebelum membuat PR.');

        $partOrder->update([
            'handled_by' => $user->id,
            'need_date' => $request->need_date,
            'warehouse_note' => $request->warehouse_note,
        ]);

        $result = $qad->createRequisition($partOrder);

        if (! $result['success']) {
            $partOrder->update(['qad_response' => $result['message']]);

            return back()->with('error', "Gagal membuat PR: {$result['message']}");
        }

        $partOrder->update([
            'pr_number' => $result['qad_req_no'],
            'status' => 'pr_created',
            'pr_date' => now()->toDateString(),
            'expected_arrival' => now()->addDays(30)->toDateString(),
            'qad_response' => $result['message'],
        ]);

        if ($partOrder->workOrder) {
            $partOrder->workOrder->update(['status' => 'parts_ordered']);
            $partOrder->workOrder->addHistory($user->id, 'parts_ordered',
                "PR dibuat: {$result['qad_req_no']}. Estimasi tiba: ".now()->addDays(30)->format('d M Y'));
        }

        return back()->with('success', "PR {$result['qad_req_no']} berhasil dibuat. Estimasi tiba ".now()->addDays(30)->format('d M Y').'.');
    }

    // Warehouse — or the WO's own assigned staffer — records a real goods
    // receipt against the QAD PO, per line, and only advances the WO once
    // every line's cumulative received qty (per QAD, not our own guess)
    // has reached what was ordered. Supports partial receiving: whatever
    // isn't fully received yet just stays queued for next time.
    public function receive(Request $request, WoPartOrder $partOrder, ApprovalService $service, QadRequisitionService $qad)
    {
        $actor = Auth::user();
        abort_unless($this->canManageOrder($actor, $partOrder), 403);
        abort_unless($partOrder->status === 'pr_created', 422);
        $partOrder->load('lines');
        abort_unless($partOrder->poNumbers() !== [], 422, 'PO QAD belum tersedia. Cek status PO dulu sebelum menerima barang.');
        abort_unless($actor->canReceiveInQad(), 403, 'Kamu belum punya login QAD sendiri — hubungi admin untuk didaftarkan sebagai penerima barang.');

        $request->validate([
            'note' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.line_id' => 'required|integer|exists:wo_part_order_lines,id',
            'items.*.qty_received' => 'required|numeric|min:0',
        ]);

        $result = $this->processReceive($partOrder, $request->items, $request->note, $actor, $service, $qad);

        return $result['success']
            ? back()->with('success', $result['message'])
            : back()->withInput()->with('error', $result['message']);
    }

    // Same receiving flow as receive(), but triggered from the PR/PO/Receiving
    // history page's per-PO popup instead of a specific order's own page —
    // items there are scoped to one PO number and may (in principle) belong
    // to more than one WoPartOrder, so this groups by owning order first.
    public function receiveByPo(Request $request, ApprovalService $service, QadRequisitionService $qad)
    {
        $actor = Auth::user();

        $request->validate([
            'po_no' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.line_id' => 'required|integer|exists:wo_part_order_lines,id',
            'items.*.qty_received' => 'required|numeric|min:0',
        ]);

        $lines = WoPartOrderLine::whereIn('id', collect($request->items)->pluck('line_id'))
            ->where('qad_po_no', $request->po_no)
            ->get()
            ->keyBy('id');

        $byOrder = collect($request->items)
            ->filter(fn ($item) => $lines->has($item['line_id']))
            ->groupBy(fn ($item) => $lines[$item['line_id']]->wo_part_order_id);

        if ($byOrder->isEmpty()) {
            return redirect()->route('warehouse.pr-po-history', ['tab' => 'po'])
                ->with('error', 'Tidak ada item valid untuk PO ini.');
        }

        $messages = [];
        $anyFailed = false;

        foreach ($byOrder as $orderId => $items) {
            $order = WoPartOrder::findOrFail($orderId);
            abort_unless($this->canManageOrder($actor, $order), 403);
            abort_unless($actor->canReceiveInQad(), 403, 'Kamu belum punya login QAD sendiri — hubungi admin untuk didaftarkan sebagai penerima barang.');

            $result = $this->processReceive($order, $items->toArray(), null, $actor, $service, $qad);
            $messages[] = $result['message'];

            if (! $result['success']) {
                $anyFailed = true;
            }
        }

        return redirect()->route('warehouse.pr-po-history', ['tab' => 'po'])
            ->with($anyFailed ? 'error' : 'success', implode(' ', $messages));
    }

    /**
     * Core receiving logic shared by receive() (per-order page) and
     * receiveByPo() (PR/PO/Receiving history popup) — validates qty against
     * QAD before/after per line, groups by the line's own PO number (a PR
     * can be split across more than one PO), and only marks the order fully
     * received once every line has reached its ordered quantity in QAD.
     *
     * @param  array  $items  [['line_id' => int, 'qty_received' => numeric], ...]
     * @return array{success: bool, message: string}
     */
    private function processReceive(WoPartOrder $order, array $items, ?string $note, User $actor, ApprovalService $service, QadRequisitionService $qad): array
    {
        $order->loadMissing('lines', 'workOrder');

        // Line numbers are positional (1-based) — must match the order
        // lines were sent to QAD in when the PR was created (WoPartOrder::lines()
        // is explicitly ordered by id for exactly this reason). This is the
        // requisition-wide line number, used for getReceivedQtyByLine()
        // verification below (SDI_getPRtoPO_'s ReqLine is PR-relative).
        $lineNoByLineId = [];
        foreach ($order->lines as $i => $line) {
            $lineNoByLineId[$line->id] = $i + 1;
        }

        // But when QAD splits a PR across more than one PO, each PO gets
        // its OWN line numbering starting back at 1 — it does not preserve
        // the original PR's line numbers. Confirmed live: a 3-line PR split
        // 2/1 across two POs had its lone line under the second PO come
        // back as PO line 1, not PR line 3; submitting "3" to SDI_eKanbanGR
        // for that PO fails with "Line item does not exist". So the number
        // actually sent to receivePurchaseOrder() must be the line's
        // position among only the lines sharing its own PO number.
        $poLineNoByLineId = [];
        foreach ($order->lines->groupBy('qad_po_no') as $linesForPo) {
            foreach ($linesForPo->values() as $i => $line) {
                $poLineNoByLineId[$line->id] = $i + 1;
            }
        }

        $before = $qad->getReceivedQtyByLine($order->pr_number);

        // QAD can split one PR across more than one PO (e.g. by vendor) —
        // group what's being received by each line's own PO number, since
        // receivePurchaseOrder's ordernum is one PO per call. A line whose
        // PO isn't known yet is skipped (can't receive against nothing).
        $qadLines = [];
        $groups = [];
        $skippedNoPo = false;
        foreach ($items as $item) {
            $qty = (float) $item['qty_received'];
            if ($qty <= 0 || ! isset($lineNoByLineId[$item['line_id']])) {
                continue;
            }
            $line = $order->lines->firstWhere('id', $item['line_id']);
            $poNo = $line->qad_po_no ?: $order->qad_po_no;
            if (! $poNo) {
                $skippedNoPo = true;

                continue;
            }
            // Verification (against $after below) stays PR-relative; the
            // actual QAD submission uses the PO-relative number instead.
            $qadLines[] = ['line' => $lineNoByLineId[$item['line_id']], 'qty' => $qty];
            $groups[$poNo][] = ['line' => $poLineNoByLineId[$item['line_id']] ?? $lineNoByLineId[$item['line_id']], 'qty' => $qty];
        }

        if (empty($groups)) {
            return ['success' => false, 'message' => $skippedNoPo
                ? 'Item yang diisi belum punya No. PO — cek status PO dulu sebelum menerima barang.'
                : 'Isi minimal 1 qty yang diterima.'];
        }

        $warnings = [];
        foreach ($groups as $poNo => $groupLines) {
            $result = $qad->receivePurchaseOrder($poNo, $order, $groupLines, $actor);

            if (! $result['success']) {
                return ['success' => false, 'message' => "Gagal catat penerimaan ke QAD (PO {$poNo}): {$result['message']}"];
            }

            if ($result['qad_warning']) {
                $warnings[] = "PO {$poNo}: {$result['qad_warning']}";
            }
        }

        // QAD's own result field isn't trustworthy either way — it can say
        // "success" without the qty actually posting, or "error" (e.g. a
        // GL/costing sub-failure) even though the qty posted fine. Re-read
        // and only trust the receipt if it really moved.
        $after = $qad->getReceivedQtyByLine($order->pr_number);

        foreach ($qadLines as $qadLine) {
            $expected = ($before[$qadLine['line']] ?? 0.0) + $qadLine['qty'];
            $actual = $after[$qadLine['line']] ?? 0.0;

            if ($actual < $expected) {
                return ['success' => false, 'message' => "Qty belum ter-update di data QAD (baris {$qadLine['line']}: baru {$actual} dari {$expected} yang diharapkan). ".
                    'Belum dianggap diterima — silakan cek/coba lagi beberapa saat lagi.'];
            }
        }

        $fullyReceived = true;
        foreach ($lineNoByLineId as $lineId => $lineNo) {
            $line = $order->lines->firstWhere('id', $lineId);
            if (($after[$lineNo] ?? 0.0) < (float) $line->quantity) {
                $fullyReceived = false;
                break;
            }
        }

        $order->update([
            'warehouse_note' => $order->warehouse_note.($note ? "\n[Receiving] ".$note : ''),
            // Keep the raw QAD text here (an internal detail field, not a
            // flash message) so it's still on record for whoever needs to
            // chase a GL/costing issue up on the QAD side.
            'qad_response' => $warnings ? implode(' | ', $warnings) : 'Penerimaan PO berhasil dicatat di QAD.',
            ...($fullyReceived ? ['status' => 'received', 'received_at' => now()] : []),
        ]);

        // Qty is confirmed moved in QAD regardless of what result/warnings
        // said — but still worth a plain-language heads-up when QAD flagged
        // something (e.g. a GL/costing posting issue), without dumping its
        // raw Progress ABL field-error text on the warehouse user.
        $qadNote = $warnings
            ? ' Catatan: ada kendala pencatatan akuntansi di sisi QAD untuk transaksi ini — barang tetap tercatat diterima, detail teknis tersimpan di catatan order untuk ditindaklanjuti tim QAD bila perlu.'
            : '';

        if ($fullyReceived) {
            if ($order->workOrder) {
                $service->onPartsReceived($order->workOrder, $actor);
            }

            $suffix = $order->workOrder ? ' WO akan dilanjutkan.' : '';

            return ['success' => true, 'message' => 'Semua barang sudah diterima dan tercatat di QAD.'.$suffix.$qadNote];
        }

        return ['success' => true, 'message' => 'Penerimaan sebagian tercatat di QAD. Masih ada item yang belum lengkap — lanjutkan kapan saja.'.$qadNote];
    }

    // Catches up an order whose items were received manually straight in
    // QAD (not through CODER's own Receive form) — every live view already
    // reflects that qty from QAD, but our own status/received_at and the
    // WO's progression only ever advance inside processReceive(), which
    // requires at least one newly-submitted qty > 0. If everything is
    // already fully received in QAD there's nothing new to submit, so that
    // normal path can never fire — this is the explicit "yes, I see it's
    // done in QAD, mark it done here too" action for exactly that case.
    public function syncReceivedStatus(WoPartOrder $partOrder, ApprovalService $service, QadRequisitionService $qad)
    {
        $actor = Auth::user();
        abort_unless($this->canManageOrder($actor, $partOrder), 403);
        abort_unless($partOrder->status === 'pr_created', 422);
        $partOrder->load('lines');
        abort_unless($partOrder->poNumbers() !== [], 422, 'PO QAD belum tersedia.');

        $received = $qad->getReceivedQtyByLine($partOrder->pr_number);

        if ($qad->lastError()) {
            return back()->with('error', 'Gagal menghubungi QAD, status penerimaan belum bisa dicek. Coba lagi beberapa saat lagi.');
        }

        $incomplete = [];
        foreach ($partOrder->lines as $i => $line) {
            $qty = $received[$i + 1] ?? 0.0;
            if ($qty < (float) $line->quantity) {
                $incomplete[] = $line->description;
            }
        }

        if (! empty($incomplete)) {
            return back()->with('error',
                'Belum semua item tercatat diterima penuh di QAD, jadi belum bisa disinkronkan: '.implode(', ', $incomplete).'.');
        }

        $partOrder->update([
            'warehouse_note' => $partOrder->warehouse_note."\n[Sync] Status disinkronkan dari QAD — seluruh item sudah tercatat diterima di QAD (kemungkinan diterima manual di luar CODER).",
            'status' => 'received',
            'received_at' => now(),
        ]);

        if ($partOrder->workOrder) {
            $service->onPartsReceived($partOrder->workOrder, $actor);
        }

        $suffix = $partOrder->workOrder ? ' WO akan dilanjutkan.' : '';

        return back()->with('success', 'Status disinkronkan — seluruh item sudah tercatat diterima di QAD.'.$suffix);
    }

    // Show detail of a part order / WO procurement
    public function showOrder(Request $request, WoPartOrder $partOrder, QadItemService $items, QadRequisitionService $qad)
    {
        $partOrder->load(['workOrder.requester', 'lines.qadItem', 'requestedBy', 'handledBy']);
        $actor = Auth::user();
        abort_unless($this->canManageOrder($actor, $partOrder), 403);

        $results = $items->browse($request->q, 30);
        $itemMasterCount = QadItem::active()->withoutExcludedProdLines()->count();
        $canReceive = $actor->canReceiveInQad();

        $poNumbers = $partOrder->poNumbers();

        // Live remaining-to-receive qty per line, straight from QAD — only
        // meaningful once a real PO exists and it's not fully received yet.
        $receivedByLineId = [];
        $allAlreadyReceived = false;
        if (! empty($poNumbers) && $partOrder->status === 'pr_created') {
            $receivedByLine = $qad->getReceivedQtyByLine($partOrder->pr_number);
            foreach ($partOrder->lines as $i => $line) {
                $receivedByLineId[$line->id] = $receivedByLine[$i + 1] ?? 0.0;
            }
            // Every line already at/above its ordered qty in QAD, but our
            // own status is still pr_created — can only happen if someone
            // received it manually straight in QAD, bypassing CODER's own
            // Receive form entirely (see syncReceivedStatus()).
            $allAlreadyReceived = $partOrder->lines->isNotEmpty()
                && $partOrder->lines->every(fn ($line) => ($receivedByLineId[$line->id] ?? 0.0) >= (float) $line->quantity);
        }

        // Isi setiap PO langsung dari QAD — keyed per PO number since a
        // split PR has more than one to show.
        $poLinesByPo = [];
        foreach ($poNumbers as $poNo) {
            $poLinesByPo[$poNo] = $qad->findPurchaseOrderLines($poNo, $partOrder->pr_date);
        }

        return view('warehouse.order', compact('partOrder', 'results', 'itemMasterCount', 'receivedByLineId', 'canReceive', 'poLinesByPo', 'allAlreadyReceived'));
    }

    // On-demand check of whether QAD has approved & converted this PR to a
    // PO yet — QAD can split one PR's lines across more than one PO (e.g.
    // by vendor), so this also writes each line's own PO number, not just
    // a single order-level one.
    public function checkPo(WoPartOrder $partOrder, QadRequisitionService $qad)
    {
        abort_unless($this->canManageOrder(Auth::user(), $partOrder), 403);
        abort_unless($partOrder->pr_number, 422, 'Order ini belum punya No. PR.');

        $result = $qad->findPurchaseOrder($partOrder->pr_number);

        if (! $result && $qad->lastError()) {
            return back()->with('error', 'Gagal menghubungi QAD, status PR belum bisa dicek. Coba lagi beberapa saat lagi.');
        }

        if (! $result) {
            return back()->with('success', 'Belum ada respons dari QAD — PR masih menunggu approval.');
        }

        $partOrder->update([
            'qad_approval_status' => $result['approval_status'],
            'qad_po_no' => $result['po_no'] ?? $partOrder->qad_po_no,
        ]);

        $partOrder->load('lines');
        foreach ($partOrder->lines as $i => $line) {
            $lineResult = $result['lines'][$i + 1] ?? null;
            if ($lineResult && $lineResult['po_no']) {
                $line->update(['qad_po_no' => $lineResult['po_no'], 'qad_po_status' => $lineResult['po_status']]);
            }
        }

        if ($result['approval_status'] !== '2') {
            return back()->with('success', 'Belum disetujui — PR masih menunggu approval di QAD.');
        }

        $partOrder->refresh()->load('lines');
        $poNumbers = $partOrder->poNumbers();

        if (empty($poNumbers)) {
            return back()->with('success', 'PR sudah disetujui — menunggu No. PO diterbitkan QAD.');
        }

        if (count($poNumbers) > 1) {
            return back()->with('success', 'PR sudah disetujui — di-split QAD jadi '.count($poNumbers).' PO: '.implode(', ', $poNumbers).'.');
        }

        return back()->with('success', "PR sudah disetujui — PO {$poNumbers[0]}.");
    }

    // Add a chosen QAD item (or a manually-typed one) as a PR line
    public function addLine(Request $request, WoPartOrder $partOrder)
    {
        $user = Auth::user();
        abort_unless($this->canManageOrder($user, $partOrder), 403);
        abort_unless($partOrder->status === 'pending_warehouse', 422, 'Order sudah diproses.');

        $request->validate([
            'mode' => 'required|in:catalog,custom',
            'qad_item_id' => 'required_if:mode,catalog|nullable|integer|exists:qad_items,id',
            'description' => 'required_if:mode,custom|nullable|string|max:255',
            'quantity' => 'required|integer|min:1',
            'uom' => 'required|string|max:20',
        ]);

        $line = [
            'quantity' => $request->quantity,
            'uom' => $request->uom,
            'added_by' => $user->id,
        ];

        if ($request->mode === 'catalog') {
            $item = QadItem::findOrFail($request->qad_item_id);
            $line += [
                'qad_item_id' => $item->id,
                'part_code' => $item->qad_code,
                'description' => $item->description ?: $item->qad_code,
                'is_custom' => false,
            ];
        } else {
            $name = trim((string) $request->description);
            $line += [
                'qad_item_id' => null,
                'part_code' => $name,
                'description' => $name,
                'is_custom' => true,
            ];
        }

        $partOrder->lines()->create($line);

        return back()->with('success', 'Item ditambahkan.');
    }

    // Remove a previously-added PR line
    public function removeLine(WoPartOrder $partOrder, WoPartOrderLine $line)
    {
        $user = Auth::user();
        abort_unless($this->canManageOrder($user, $partOrder), 403);
        abort_unless($partOrder->status === 'pending_warehouse', 422, 'Order sudah diproses.');
        abort_unless($line->wo_part_order_id === $partOrder->id, 404);

        $line->delete();

        return back()->with('success', 'Item dihapus.');
    }

    /**
     * Warehouse MTC / MTC Section Head manage Maintenance orders;
     * GA Section Head manages GA orders (same PR flow). Works for
     * standalone orders too (no parent WO) via WoPartOrder::targetDepartmentId().
     */
    private function canManageOrder(User $user, WoPartOrder $order): bool
    {
        return $user->managesWarehouseForDepartment($order->targetDepartmentId());
    }

    /**
     * Paginate distinct PO numbers manually — Eloquent's paginate() doesn't
     * compose reliably with distinct() (its COUNT query counts the
     * underlying rows, not the distinct values), so this pulls every
     * matching PO number once (a cheap single-column query), then slices
     * and wraps that in a real LengthAwarePaginator by hand.
     */
    private function paginateDistinctPoNumbers(\Closure $scopeLines, Request $request): LengthAwarePaginator
    {
        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();

        $query = WoPartOrderLine::whereNotNull('qad_po_no');
        $scopeLines($query);

        $allPoNumbers = $query->select('qad_po_no')->distinct()->orderByDesc('qad_po_no')->pluck('qad_po_no');

        return new LengthAwarePaginator(
            $allPoNumbers->forPage($page, $perPage)->values(),
            $allPoNumbers->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );
    }
}
