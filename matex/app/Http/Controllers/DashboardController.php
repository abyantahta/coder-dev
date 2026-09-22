<?php

namespace App\Http\Controllers;

use App\Enums\PoStatus;
use App\Enums\ScheduleStatus;
use App\Enums\UserRole;
use App\Models\DeliveryNote;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Receiving;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $mode = $request->string('mode')->toString() === 'item' ? 'item' : 'po';
        $search = $request->string('search')->toString();
        $runningStatuses = [
            PoStatus::AwaitingRmConfirm,
            PoStatus::AwaitingPurchasingOk,
            PoStatus::Confirmed,
            PoStatus::InProgress,
        ];

        $stats = match ($user->role) {
            UserRole::Purchasing, UserRole::Admin => [
                'draft' => PurchaseOrder::where('status', PoStatus::Draft)->count(),
                'awaiting_rm' => PurchaseOrder::where('status', PoStatus::AwaitingRmConfirm)->count(),
                'awaiting_ok' => PurchaseOrder::where('status', PoStatus::AwaitingPurchasingOk)->count(),
                'confirmed' => PurchaseOrder::whereIn('status', [PoStatus::Confirmed, PoStatus::InProgress])->count(),
            ],
            UserRole::SupplierRm => [
                'to_confirm' => PurchaseOrder::where('supplier_rm_id', $user->company_id)
                    ->where('status', PoStatus::AwaitingRmConfirm)->count(),
                'to_ship' => DeliveryNote::whereHas('purchaseOrder', fn ($q) => $q->where('supplier_rm_id', $user->company_id))
                    ->whereHas('deliverySchedule', fn ($q) => $q->where('status', ScheduleStatus::Planned))->count(),
                'received' => Receiving::whereHas('deliveryNote.purchaseOrder', fn ($q) => $q->where('supplier_rm_id', $user->company_id))->count(),
            ],
            UserRole::SupplierOhp => [
                'incoming' => DeliveryNote::whereHas('deliverySchedule', fn ($q) => $q
                    ->where('ohp_supplier_id', $user->company_id)
                    ->where('status', ScheduleStatus::ShipConfirmed))->count(),
                'confirmed' => DeliveryNote::whereHas('deliverySchedule', fn ($q) => $q->where('ohp_supplier_id', $user->company_id))
                    ->whereHas('ohpConfirmation')->count(),
            ],
            UserRole::Ppic => [
                // status stays OhpOk until a DN is fully received (partial
                // receipts don't flip it), so this alone covers "not yet
                // fully received" without needing a whereDoesntHave here.
                'ready_receive' => DeliveryNote::whereHas('deliverySchedule', fn ($q) => $q->where('status', ScheduleStatus::OhpOk))->count(),
                'received' => Receiving::count(),
            ],
            default => [],
        };

        $recentPos = PurchaseOrder::query()
            ->visibleTo($user)
            ->withFulfillmentCounts()
            ->with(['supplierRm'])
            ->withSchedulesVisibleTo($user)
            ->latest()
            ->limit(8)
            ->get()
            ->each(fn (PurchaseOrder $po) => $po->append('is_closed'));

        $scopeRunningPo = function ($q) use ($user, $runningStatuses) {
            $q->whereIn('status', $runningStatuses)->visibleTo($user);
        };

        $itemRows = Item::query()
            ->whereHas('purchaseOrderItems.purchaseOrder', $scopeRunningPo)
            ->when($search !== '', function ($q) use ($search) {
                $term = '%'.$search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('item_number', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->with([
                'purchaseOrderItems' => function ($q) use ($scopeRunningPo, $user) {
                    $q->whereHas('purchaseOrder', $scopeRunningPo)
                        ->with(['purchaseOrder' => function ($pq) use ($user) {
                            $pq->withFulfillmentCounts()
                                ->with(['supplierRm'])
                                ->withSchedulesVisibleTo($user);
                        }]);
                },
            ])
            ->orderBy('item_number')
            ->paginate(12)
            ->withQueryString()
            ->through(function (Item $item) {
                $pos = $item->purchaseOrderItems
                    ->groupBy('purchase_order_id')
                    ->map(function ($lines) {
                        $po = $lines->first()?->purchaseOrder;
                        if (! $po) {
                            return null;
                        }

                        $qty = (int) round($lines->sum(fn ($line) => (float) ($line->qty_confirmed ?? $line->qty_ordered)));

                        $po->append('is_closed');

                        return [
                            'id' => $po->id,
                            'po_number' => $po->po_number,
                            'status' => $po->status instanceof PoStatus ? $po->status->value : $po->status,
                            'is_closed' => $po->is_closed,
                            'supplier_rm' => $po->supplierRm,
                            'due_date' => $po->due_date,
                            'qty' => $qty,
                        ];
                    })
                    ->filter()
                    ->values();

                return [
                    'id' => $item->id,
                    'item_number' => $item->item_number,
                    'description' => $item->description,
                    'uom' => $item->uom,
                    'running_po_count' => $pos->count(),
                    'total_qty' => (int) $pos->sum('qty'),
                    'purchase_orders' => $pos,
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentPos' => $recentPos,
            'itemRows' => $itemRows,
            'mode' => $mode,
            'filters' => [
                'search' => $search,
            ],
            'role' => $user->role->value,
            'roleLabel' => $user->role->label(),
        ]);
    }
}
