<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Receiving;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole(UserRole::SupplierRm, UserRole::Purchasing, UserRole::Admin, UserRole::Ppic),
            403
        );

        $baseQuery = Receiving::query()
            ->when(
                $user->hasRole(UserRole::SupplierRm),
                fn ($q) => $q->whereHas(
                    'deliveryNote.purchaseOrder',
                    fn ($pq) => $pq->where('supplier_rm_id', $user->company_id)
                )
            );

        $summary = [
            'total_dn' => (clone $baseQuery)->count(),
            'total_qty' => (clone $baseQuery)->sum('received_qty'),
        ];

        $receivings = (clone $baseQuery)
            ->with([
                'deliveryNote.purchaseOrder.supplierRm',
                'deliveryNote.purchaseOrderItem.item',
                'deliveryNote.deliverySchedule',
                'receiver',
            ])
            ->latest('received_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Billing/Index', [
            'receivings' => $receivings,
            'summary' => $summary,
        ]);
    }
}
