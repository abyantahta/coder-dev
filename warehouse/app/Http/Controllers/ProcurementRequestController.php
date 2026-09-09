<?php

namespace App\Http\Controllers;

use App\Models\ProcurementItem;
use App\Models\ProcurementRequest;
use App\Models\ProcurementRequestAttachment;
use App\Models\Uom;
use App\Models\User;
use App\Notifications\SectionApprovalNeededNotification;
use App\Services\BudgetService;
use App\Services\FileCompressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class ProcurementRequestController extends Controller
{
    public function __construct(
        private BudgetService $budgetService,
        private FileCompressionService $fileCompression,
    ) {}

    /** Gabungan status ProcurementRequest sendiri + status PurchaseRequest hasil agregasi GA (dipakai buat dropdown filter). */
    public const STATUS_OPTIONS = [
        'draft'              => 'Draft',
        'pending_section'    => 'Menunggu Section/Dept Head',
        'pending_ga'         => 'Menunggu Review GA',
        'rejected'           => 'Ditolak',
        'sent_to_qad'        => 'Terkirim ke QAD',
        'send_failed'        => 'Gagal Kirim ke QAD',
        'director_confirmed' => 'Disetujui Direktur',
        'director_denied'    => 'Ditolak Direktur (Direvisi GA)',
        'received'           => 'Barang Diterima',
        'partially_received' => 'Diterima Sebagian',
        'distributing'       => 'Sedang Didistribusikan',
        'completed'          => 'Selesai',
    ];

    /** Status yang sebenarnya milik PurchaseRequest (tahap setelah di-agregasi GA), bukan status ProcurementRequest sendiri. */
    private const AGGREGATED_STATUSES = [
        'sent_to_qad', 'send_failed', 'director_confirmed', 'director_denied',
        'received', 'partially_received', 'distributing', 'completed',
    ];

    public function index(Request $request)
    {
        $query = ProcurementRequest::with(['user', 'department', 'details', 'aggregatedPr'])
            ->where('user_id', auth()->id());

        if ($request->filled('date_from')) {
            $query->whereDate('req_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('req_date', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if (in_array($status, self::AGGREGATED_STATUSES)) {
                $query->where('status', 'aggregated')
                    ->whereHas('aggregatedPr', fn($q) => $q->where('status', $status));
            } else {
                $query->where('status', $status);
            }
        }

        $requests = $query->latest('req_date')->latest('id')->paginate(15)->withQueryString();

        return view('procurement.requests.index', [
            'requests'       => $requests,
            'statusOptions'  => self::STATUS_OPTIONS,
        ]);
    }

    public function create()
    {
        $uoms = Uom::where('is_active', true)->orderBy('code')->get();
        $defaultPurpose = 'Kebutuhan ATK bulan ' . $this->indonesianMonthName(now()->addMonthNoOverflow());
        return view('procurement.requests.create', compact('uoms', 'defaultPurpose'));
    }

    /** Nama bulan Indonesia — dipakai buat default "Kebutuhan ATK" bulan depan, lepas dari APP_LOCALE. */
    private function indonesianMonthName(\Carbon\Carbon $date): string
    {
        $names = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        return $names[$date->month] . ' ' . $date->year;
    }

    public function store(Request $request)
    {
        $request->validate([
            'purpose'       => 'required|string|max:255',
            'notes'         => 'nullable|string|max:500',
            'items'         => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:procurement_items,id',
            'items.*.qty'     => 'required|numeric|min:0.01',
            'items.*.uom_id'  => 'required|exists:uoms,id',
            'attachments'     => 'nullable|array|max:5',
            'attachments.*'   => 'file|mimes:jpg,jpeg,pdf,xlsx,xls|max:10240',
        ]);

        $pr = DB::transaction(function () use ($request) {
            $pr = ProcurementRequest::create([
                'req_no'        => ProcurementRequest::generateReqNo(),
                'user_id'       => auth()->id(),
                'department_id' => auth()->user()->department_id,
                'purpose'       => $request->purpose,
                'status'        => 'draft',
                'notes'         => $request->notes,
                'req_date'      => today(),
            ]);

            foreach ($request->items as $item) {
                $procurementItem = ProcurementItem::find($item['item_id']);
                $amount = (float) $item['qty'] * (float) ($procurementItem->price ?? 0);
                $isOverBudget = $this->budgetService->isOverBudget(auth()->user()->department_id, $item['item_id'], $amount);

                $pr->details()->create([
                    'item_id'        => $item['item_id'],
                    'qty'            => $item['qty'],
                    'uom_id'         => $item['uom_id'],
                    'notes'          => $item['notes'] ?? null,
                    'is_over_budget' => $isOverBudget,
                ]);
            }
            return $pr;
        });

        foreach ($request->file('attachments', []) as $file) {
            $stored = $this->fileCompression->store($file, 'procurement/attachments');

            $pr->attachments()->create([
                'file_path'     => $stored['path'],
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getClientMimeType(),
                'size'          => $stored['size'],
                'uploaded_by'   => auth()->id(),
            ]);
        }

        if ($request->action === 'submit') {
            $pr->update(['status' => 'pending_section']);
            $this->notifySectionApprovers($pr);
            return redirect()->route('procurement.requests.show', $pr->id)
                ->with('success', "Permintaan {$pr->req_no} berhasil diajukan ke Section.");
        }

        return redirect()->route('procurement.requests.show', $pr->id)
            ->with('success', "Draft {$pr->req_no} tersimpan.");
    }

    public function show(ProcurementRequest $procurementRequest)
    {
        if ($procurementRequest->user_id !== auth()->id() && !auth()->user()->isAdmin() && !in_array(auth()->user()->role, ['section','ga','director','superadmin'])) {
            abort(403);
        }
        $procurementRequest->load(['user', 'department', 'details.item.uom', 'details.uom', 'sectionBy', 'gaBy', 'aggregatedPr.directorBy', 'aggregatedPr.receivedBy', 'attachments']);

        $budgetHistory = [];
        $itemIds = $procurementRequest->details->pluck('item_id')->unique();
        if ($procurementRequest->department_id && $itemIds->isNotEmpty()) {
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $budget = 0;
                $consumed = 0;
                foreach ($itemIds as $itemId) {
                    $budget   += $this->budgetService->effectiveBudget($procurementRequest->department_id, $itemId, $date->year, $date->month);
                    $consumed += $this->budgetService->consumed($procurementRequest->department_id, $itemId, $date->year, $date->month);
                }
                $budgetHistory[] = ['label' => $date->translatedFormat('M Y'), 'budget' => $budget, 'consumed' => $consumed];
            }
        }

        return view('procurement.requests.show', compact('procurementRequest', 'budgetHistory'));
    }

    public function submit(ProcurementRequest $procurementRequest)
    {
        if ($procurementRequest->user_id !== auth()->id() || $procurementRequest->status !== 'draft') {
            return back()->with('error', 'Tidak bisa submit permintaan ini.');
        }
        $procurementRequest->update(['status' => 'pending_section']);
        $this->notifySectionApprovers($procurementRequest);
        return back()->with('success', 'Permintaan berhasil diajukan ke Section untuk disetujui.');
    }

    private function notifySectionApprovers(ProcurementRequest $pr): void
    {
        $approvers = User::where('role', 'section')
            ->where('is_active', true)
            ->where(fn($q) => $q->where('department_id', $pr->department_id)
                ->orWhereHas('approverDepartments', fn($q2) => $q2->where('departments.id', $pr->department_id)))
            ->get();

        Notification::send($approvers, new SectionApprovalNeededNotification($pr));
    }

    public function destroyAttachment(ProcurementRequestAttachment $attachment)
    {
        if ($attachment->uploaded_by !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        Storage::disk('public')->delete($attachment->file_path);
        $requestId = $attachment->request_id;
        $attachment->delete();

        return redirect()->route('procurement.requests.show', $requestId)
            ->with('success', 'Lampiran berhasil dihapus.');
    }

    public function allRequests()
    {
        $requests = ProcurementRequest::with(['user', 'department', 'details'])
            ->latest()->paginate(20);
        return view('procurement.requests.all', compact('requests'));
    }
}
