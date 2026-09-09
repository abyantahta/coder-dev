<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    public function index()
    {
        $prs = PurchaseRequest::with(['creator', 'details.item'])
            ->latest()
            ->paginate(15);
        return view('admin.purchase-requests.index', compact('prs'));
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load(['creator', 'details.item']);
        return view('admin.purchase-requests.show', compact('purchaseRequest'));
    }

    public function submit(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->source !== 'min_stock' || $purchaseRequest->status !== 'draft') {
            return back()->with('error', 'PR ini tidak bisa disubmit lewat halaman ini.');
        }
        $purchaseRequest->update(['status' => 'submitted', 'submitted_at' => now()]);
        return back()->with('success', "PR {$purchaseRequest->pr_no} berhasil disubmit ke Purchasing.");
    }

    /**
     * Hanya untuk PR sumber min_stock. PR hasil agregasi GA statusnya diatur lewat
     * DirectorPurchaseRequestController (approve/reject) dan GaReceivingController (receiving).
     */
    public function updateStatus(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->source !== 'min_stock') {
            return back()->with('error', 'Status PR ini dikelola lewat alur approval GA/Direktur.');
        }
        $request->validate(['status' => 'required|in:draft,submitted,completed,cancelled']);
        $purchaseRequest->update(['status' => $request->status]);
        return back()->with('success', 'Status PR berhasil diperbarui.');
    }

    public function printPdf(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load(['creator', 'details.item']);
        $pdf = Pdf::loadView('admin.purchase-requests.pdf', compact('purchaseRequest'));
        return $pdf->stream("PR-{$purchaseRequest->pr_no}.pdf");
    }
}
