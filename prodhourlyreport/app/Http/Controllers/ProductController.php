<?php

namespace App\Http\Controllers;

use App\Jobs\SyncQadProductsJob;
use App\Models\Line;
use App\Models\Product;
use App\Models\ProductModel;
use App\Support\ProductCategories;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $filters = [
            'search' => $request->string('search')->trim()->toString() ?: null,
            'line_id' => $request->integer('line_id') ?: null,
            'category' => $request->string('category')->trim()->toString() ?: null,
            'page' => max(1, $request->integer('page') ?: 1),
        ];

        $products = Product::query()
            ->with('productModel:id,name,line_id', 'productModel.line:id,name')
            ->when($filters['search'], function ($query, string $search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like)
                        ->orWhere('part_number', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('qad_code', 'like', $like);
                });
            })
            ->when($filters['line_id'], function ($query, int $lineId) {
                $query->whereHas('productModel', fn ($q) => $q->where('line_id', $lineId));
            })
            ->when($filters['category'], function ($query, string $category) {
                $query->where('category', $category);
            })
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('MasterData/Products/Index', [
            'products' => $products,
            'productModels' => ProductModel::with('line:id,name')->orderBy('name')->get(['id', 'name', 'line_id']),
            'lines' => Line::orderBy('name')->get(['id', 'name']),
            'categories' => ProductCategories::options(),
            'filters' => $filters,
            'syncStatus' => Cache::get(SyncQadProductsJob::CACHE_KEY),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $request->merge([
            'product_model_id' => $request->filled('product_model_id')
                ? $request->input('product_model_id')
                : null,
        ]);

        $data = $request->validate([
            'product_model_id' => ['nullable', 'exists:product_models,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products')->where('product_model_id', $request->input('product_model_id')),
            ],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        Product::create($data);

        return back()->with('success', 'Product created.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $request->merge([
            'product_model_id' => $request->filled('product_model_id')
                ? $request->input('product_model_id')
                : null,
        ]);

        $data = $request->validate([
            'product_model_id' => ['nullable', 'exists:product_models,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products')
                    ->where('product_model_id', $request->input('product_model_id'))
                    ->ignore($product->id),
            ],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $product->update($data);

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    public function sync(): RedirectResponse
    {
        $this->authorize('sync', Product::class);

        $current = Cache::get(SyncQadProductsJob::CACHE_KEY);
        if (in_array($current['status'] ?? null, ['queued', 'running'], true)) {
            return back()->with('error', 'Product sync masih berjalan. Tunggu sampai selesai, lalu refresh halaman.');
        }

        Cache::put(SyncQadProductsJob::CACHE_KEY, [
            'status' => 'queued',
            'message' => 'Product sync diantrikan. Response HTTP langsung kembali; proses jalan di background.',
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
            'created' => 0,
            'updated' => 0,
            'synced' => 0,
        ], now()->addHours(6));

        // Finish HTTP first so the gateway does not 504; work continues after.
        SyncQadProductsJob::dispatch()->afterResponse();

        return back()->with(
            'success',
            'Product sync dimulai di background. Halaman ini akan refresh otomatis sampai selesai.',
        );
    }
}
