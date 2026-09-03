<?php

namespace App\Http\Controllers;

use App\Models\Line;
use App\Models\ProductionLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductionLogController extends Controller
{
    public function create(): Response
    {
        $this->authorize('create', ProductionLog::class);

        $user = Auth::user();

        $lines = $user->accessesAllLines()
            ? Line::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : $user->lines()->where('lines.is_active', true)->orderBy('lines.name')->get(['lines.id', 'lines.name']);

        $todayLogs = ProductionLog::with(['line:id,name', 'productModel:id,name', 'product:id,name'])
            ->where('user_id', $user->id)
            ->whereDate('logged_at', today())
            ->orderByDesc('logged_at')
            ->limit(100)
            ->get();

        return Inertia::render('Entry/Create', [
            'lines' => $lines,
            'todayLogs' => $todayLogs,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'line_id' => ['required', 'exists:lines,id'],
            'product_model_id' => [
                'required',
                Rule::exists('product_models', 'id')->where('line_id', $request->input('line_id')),
            ],
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where('product_model_id', $request->input('product_model_id')),
            ],
            'total_production' => ['required', 'integer', 'min:0'],
            'total_reject' => ['required', 'integer', 'min:0'],
            'total_repair' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'logged_at' => ['nullable', 'date', 'after:-7 days', 'before:+5 minutes'],
            'client_uuid' => ['nullable', 'uuid'],
        ]);

        $this->authorize('create', [ProductionLog::class, (int) $data['line_id']]);

        $attributes = [
            'line_id' => $data['line_id'],
            'product_model_id' => $data['product_model_id'],
            'product_id' => $data['product_id'],
            'user_id' => Auth::id(),
            'logged_at' => isset($data['logged_at']) ? Carbon::parse($data['logged_at'])->setTimezone(config('app.timezone')) : now(),
            'total_production' => $data['total_production'],
            'total_reject' => $data['total_reject'],
            'total_repair' => $data['total_repair'],
            'notes' => $data['notes'] ?? null,
        ];

        $log = empty($data['client_uuid'])
            ? ProductionLog::create($attributes)
            : ProductionLog::firstOrCreate(['client_uuid' => $data['client_uuid']], $attributes);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Entry recorded.',
                'log' => $log->load(['line:id,name', 'productModel:id,name', 'product:id,name']),
            ], 201);
        }

        return back()->with('success', 'Entry recorded.');
    }
}
