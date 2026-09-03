<?php

namespace App\Http\Controllers;

use App\Models\Line;
use App\Models\Product;
use App\Models\ProductModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CatalogController extends Controller
{
    /**
     * Active models for a line (scoped to the user's visible lines).
     */
    public function models(Request $request): JsonResponse
    {
        $data = $request->validate([
            'line_id' => ['required', 'integer', 'exists:lines,id'],
        ]);

        $user = Auth::user();
        abort_unless($user->canAccessLine((int) $data['line_id']), 403);

        $models = ProductModel::query()
            ->where('line_id', $data['line_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'line_id', 'code']);

        return response()->json(['data' => $models]);
    }

    /**
     * Active products for a model, with optional search/category.
     */
    public function products(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_model_id' => ['required', 'integer', 'exists:product_models,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:50'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $model = ProductModel::query()->findOrFail($data['product_model_id']);
        abort_unless(Auth::user()->canAccessLine((int) $model->line_id), 403);

        $limit = $data['limit'] ?? 50;

        $products = Product::query()
            ->where('product_model_id', $model->id)
            ->where('is_active', true)
            ->when($data['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when($data['search'] ?? null, function ($q, string $search) {
                $like = '%'.$search.'%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like)
                        ->orWhere('part_number', 'like', $like);
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'code', 'part_number', 'category', 'product_model_id']);

        return response()->json(['data' => $products]);
    }

    /**
     * Lines visible to the current user (id + name only).
     */
    public function lines(): JsonResponse
    {
        $user = Auth::user();

        $lines = $user->accessesAllLines()
            ? Line::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : $user->lines()->where('lines.is_active', true)->orderBy('lines.name')->get(['lines.id', 'lines.name']);

        return response()->json(['data' => $lines]);
    }
}
