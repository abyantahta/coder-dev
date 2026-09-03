<?php

namespace App\Http\Controllers;

use App\Models\Line;
use App\Models\ProductModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductModelController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProductModel::class);

        $filters = [
            'search' => $request->string('search')->trim()->toString() ?: null,
            'line_id' => $request->integer('line_id') ?: null,
        ];

        $productModels = ProductModel::query()
            ->with('line:id,name')
            ->withCount('products')
            ->when($filters['line_id'], fn ($query, int $lineId) => $query->where('line_id', $lineId))
            ->when($filters['search'], function ($query, string $search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like);
                });
            })
            ->orderBy('name')
            ->get();

        return Inertia::render('MasterData/ProductModels/Index', [
            'productModels' => $productModels,
            'lines' => Line::orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ProductModel::class);

        $data = $request->validate([
            'line_id' => ['required', 'exists:lines,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('product_models')->where('line_id', $request->input('line_id'))],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        ProductModel::create($data);

        return back()->with('success', 'Model created.');
    }

    public function update(Request $request, ProductModel $product_model): RedirectResponse
    {
        $this->authorize('update', $product_model);

        $data = $request->validate([
            'line_id' => ['required', 'exists:lines,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('product_models')->where('line_id', $request->input('line_id'))->ignore($product_model->id)],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $product_model->update($data);

        return back()->with('success', 'Model updated.');
    }

    public function destroy(ProductModel $product_model): RedirectResponse
    {
        $this->authorize('delete', $product_model);

        $product_model->delete();

        return back()->with('success', 'Model deleted.');
    }
}
