<?php

namespace App\Http\Controllers;

use App\Actions\PurchaseOrder\ApproveByPurchasing;
use App\Actions\PurchaseOrder\ConfirmByRm;
use App\Actions\PurchaseOrder\RejectByPurchasing;
use App\Actions\PurchaseOrder\StorePurchaseOrder;
use App\Actions\PurchaseOrder\SubmitPurchaseOrder;
use App\Actions\PurchaseOrder\UpdatePurchaseOrder;
use App\Enums\CompanyType;
use App\Enums\ScheduleStatus;
use App\Enums\UserRole;
use App\Http\Requests\ConfirmByRmRequest;
use App\Http\Requests\RejectPurchaseOrderRequest;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\QadItem;
use App\Models\QadSupplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $user = $request->user();

        $orders = PurchaseOrder::query()
            ->visibleTo($user)
            ->withFulfillmentCounts()
            ->with(['supplierRm', 'creator'])
            ->withSchedulesVisibleTo($user)
            ->when($request->string('status')->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (PurchaseOrder $po) => tap($po)->append('is_closed'));

        return Inertia::render('Po/Index', [
            'orders' => $orders,
            'filters' => [
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PurchaseOrder::class);

        return Inertia::render('Po/Form', [
            'order' => null,
            'qadSuppliers' => $this->qadSupplierOptions(CompanyType::RawMat),
            'qadSuppliersOhp' => $this->qadSupplierOptions(CompanyType::Ohp),
            'qadItems' => $this->qadItemOptions(),
            'itemDefaults' => $this->itemDefaults(),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request, StorePurchaseOrder $action): RedirectResponse
    {
        $po = $action->execute($request->user(), $request->validated());

        return redirect()
            ->route('purchase-orders.show', $po)
            ->with('success', 'Draft PO berhasil dibuat.');
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder): Response
    {
        $this->authorize('view', $purchaseOrder);

        $user = $request->user();
        $isOhp = $user->hasRole(UserRole::SupplierOhp);

        $purchaseOrder->load([
            'supplierRm',
            'creator',
            'items.item.subcontOhp',
            'schedules' => function ($q) use ($isOhp, $user) {
                if ($isOhp && $user->company_id) {
                    $q->where('ohp_supplier_id', $user->company_id);
                }
            },
            'schedules.ohpSupplier',
            'schedules.purchaseOrderItem.item',
            'schedules.deliveryNote.ohpConfirmation',
            'schedules.deliveryNote.receiving',
            'deliveryNotes' => function ($q) use ($isOhp, $user) {
                if ($isOhp && $user->company_id) {
                    $q->whereHas(
                        'deliverySchedule',
                        fn ($sq) => $sq->where('ohp_supplier_id', $user->company_id)
                    );
                }
            },
            'deliveryNotes.purchaseOrderItem.item',
            'deliveryNotes.deliverySchedule.ohpSupplier',
            'statusLogs.user',
        ]);

        $purchaseOrder->loadCount([
            'schedules',
            'schedules as unapproved_schedules_count' => fn ($q) => $q->whereNotIn(
                'status',
                ScheduleStatus::sentAndApprovedValues()
            ),
        ]);
        $purchaseOrder->append('is_closed');
        $purchaseOrder->restrictRelationsFor($user);

        // OHP tidak melihat history internal SDI ↔ Supplier RM
        if ($isOhp) {
            $purchaseOrder->unsetRelation('statusLogs');
            $purchaseOrder->setRelation('statusLogs', collect());
        }

        return Inertia::render('Po/Show', [
            'order' => $purchaseOrder,
            'hideInternalHistory' => $isOhp,
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorize('update', $purchaseOrder);

        $purchaseOrder->load(['items.item', 'schedules.ohpSupplier', 'supplierRm']);

        return Inertia::render('Po/Form', [
            'order' => $purchaseOrder,
            'qadSuppliers' => $this->qadSupplierOptions(CompanyType::RawMat),
            'qadSuppliersOhp' => $this->qadSupplierOptions(CompanyType::Ohp),
            'qadItems' => $this->qadItemOptions(),
            'itemDefaults' => $this->itemDefaults(),
        ]);
    }

    /**
     * QAD item master is the source of truth for the PO item picker — scoped
     * to prod_line RM (raw material) since that's the only line this
     * RM-procurement flow deals with, out of ~4.5k synced items overall.
     */
    private function qadItemOptions()
    {
        return QadItem::active()
            ->where('prod_line', 'RM')
            ->orderBy('description')
            ->get(['id', 'qad_code', 'description', 'uom', 'prod_line']);
    }

    /**
     * QAD supplier master is the source of truth for both the Supplier RM
     * and Supplier OHP pickers — each scoped to its own manually-tagged
     * category (qad_suppliers.category), since QAD's vendor master itself
     * doesn't distinguish RM vs OHP (tagged in Admin > Suppliers).
     *
     * Only suppliers that already have a portal account (a companies row,
     * matched by code, with at least one user) are selectable — a supplier
     * with no login can't act on the PO (confirm qty, receive DN, etc), so
     * picking one here would create a PO nobody on their side can see.
     */
    private function qadSupplierOptions(CompanyType $category)
    {
        return QadSupplier::active()
            ->where('category', $category->value)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('companies')
                    ->join('users', 'users.company_id', '=', 'companies.id')
                    ->whereColumn('companies.code', 'qad_suppliers.qad_code');
            })
            ->orderBy('name')
            ->get(['id', 'qad_code', 'name', 'city']);
    }

    /**
     * Local, matex-only enrichment (price, default subcont OHP) keyed by
     * item_number — items are auto-provisioned from the QAD item master the
     * first time they're used on a PO, then editable via Admin > Raw Material.
     * subcont_ohp_code (not id) since the PO form's OHP picker is now
     * QAD-code-keyed too.
     */
    private function itemDefaults()
    {
        return Item::query()
            ->with('subcontOhp:id,code')
            ->get(['id', 'item_number', 'subcont_ohp_id'])
            ->keyBy('item_number')
            ->map(fn (Item $item) => ['subcont_ohp_code' => $item->subcontOhp?->code]);
    }

    public function update(
        UpdatePurchaseOrderRequest $request,
        PurchaseOrder $purchaseOrder,
        UpdatePurchaseOrder $action,
    ): RedirectResponse {
        $action->execute($purchaseOrder, $request->user(), $request->validated());

        return redirect()
            ->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Draft PO berhasil diperbarui.');
    }

    public function submit(PurchaseOrder $purchaseOrder, SubmitPurchaseOrder $action): RedirectResponse
    {
        $this->authorize('submit', $purchaseOrder);
        $action->execute($purchaseOrder, request()->user());

        return back()->with('success', 'PO berhasil disubmit ke Supplier RM.');
    }

    public function confirmRm(
        ConfirmByRmRequest $request,
        PurchaseOrder $purchaseOrder,
        ConfirmByRm $action,
    ): RedirectResponse {
        $action->execute($purchaseOrder, $request->user(), $request->validated());

        return back()->with('success', 'Konfirmasi RM berhasil dikirim. Menunggu OK Purchasing.');
    }

    public function approve(
        PurchaseOrder $purchaseOrder,
        ApproveByPurchasing $action,
    ): RedirectResponse {
        $this->authorize('approveAsPurchasing', $purchaseOrder);
        $po = $action->execute($purchaseOrder, request()->user());

        $message = 'PO dikonfirmasi OK. Delivery Notes telah digenerate.';
        if ($po->qad_po_number) {
            $message .= " Tersinkron ke QAD sebagai {$po->qad_po_number}.";
        } elseif ($po->qad_status?->value === 'failed') {
            $message .= ' Sync ke QAD gagal — cek riwayat PO.';
        }

        return back()->with('success', $message);
    }

    public function reject(
        RejectPurchaseOrderRequest $request,
        PurchaseOrder $purchaseOrder,
        RejectByPurchasing $action,
    ): RedirectResponse {
        $action->execute($purchaseOrder, $request->user(), $request->validated('reason'));

        return back()->with('success', 'Konfirmasi ditolak. Supplier RM perlu submit ulang.');
    }
}
