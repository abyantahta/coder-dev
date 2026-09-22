<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreItemRequest;
use App\Http\Requests\Admin\UpdateItemRequest;
use App\Models\Company;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    public function index(Request $request): Response
    {
        $items = Item::query()
            ->with('subcontOhp:id,code,name')
            ->when($request->string('search')->isNotEmpty(), function ($q) use ($request) {
                $search = '%'.$request->string('search').'%';
                $q->where(function ($inner) use ($search) {
                    $inner->where('item_number', 'like', $search)
                        ->orWhere('description', 'like', $search);
                });
            })
            ->when($request->filled('active'), function ($q) use ($request) {
                $q->where('is_active', $request->boolean('active'));
            })
            ->orderBy('item_number')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Items/Index', [
            'items' => $items,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'active' => $request->input('active'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Items/Form', [
            'item' => null,
            'suppliersOhp' => Company::ohp()->active()->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(StoreItemRequest $request): RedirectResponse
    {
        Item::create($request->validated());

        return redirect()
            ->route('admin.items.index')
            ->with('success', 'Item berhasil ditambahkan.');
    }

    public function edit(Item $item): Response
    {
        return Inertia::render('Admin/Items/Form', [
            'item' => $item,
            'suppliersOhp' => Company::ohp()->active()->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $item->update($request->validated());

        return redirect()
            ->route('admin.items.index')
            ->with('success', 'Item berhasil diperbarui.');
    }

    public function destroy(Item $item): RedirectResponse
    {
        if ($item->purchaseOrderItems()->exists()) {
            $item->update(['is_active' => false]);

            return back()->with('success', 'Item sudah terpakai di PO. Status diubah menjadi nonaktif.');
        }

        $item->delete();

        return back()->with('success', 'Item berhasil dihapus.');
    }
}
