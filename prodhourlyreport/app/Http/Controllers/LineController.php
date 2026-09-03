<?php

namespace App\Http\Controllers;

use App\Jobs\SyncQadLinesJob;
use App\Models\Line;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LineController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Line::class);

        $filters = [
            'search' => $request->string('search')->trim()->toString() ?: null,
        ];

        $lines = Line::query()
            ->withCount(['productModels', 'productionLogs'])
            ->when($filters['search'], function ($query, string $search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like)
                        ->orWhere('qad_code', 'like', $like);
                });
            })
            ->orderBy('name')
            ->get();

        return Inertia::render('MasterData/Lines/Index', [
            'lines' => $lines,
            'filters' => $filters,
            'syncStatus' => Cache::get(SyncQadLinesJob::CACHE_KEY),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Line::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('lines', 'code')],
            'is_active' => ['boolean'],
        ]);

        Line::create($data);

        return back()->with('success', 'Line created.');
    }

    public function update(Request $request, Line $line): RedirectResponse
    {
        $this->authorize('update', $line);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('lines', 'code')->ignore($line->id)],
            'is_active' => ['boolean'],
        ]);

        $line->update($data);

        return back()->with('success', 'Line updated.');
    }

    public function destroy(Line $line): RedirectResponse
    {
        $this->authorize('delete', $line);

        $line->delete();

        return back()->with('success', 'Line deleted.');
    }

    public function sync(): RedirectResponse
    {
        $this->authorize('sync', Line::class);

        $current = Cache::get(SyncQadLinesJob::CACHE_KEY);
        if (in_array($current['status'] ?? null, ['queued', 'running'], true)) {
            return back()->with('error', 'Line sync masih berjalan. Tunggu sampai selesai, lalu refresh halaman.');
        }

        Cache::put(SyncQadLinesJob::CACHE_KEY, [
            'status' => 'queued',
            'message' => 'Line sync diantrikan. Response HTTP langsung kembali; proses jalan di background.',
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
            'created' => 0,
            'updated' => 0,
            'synced' => 0,
        ], now()->addHours(6));

        SyncQadLinesJob::dispatch()->afterResponse();

        return back()->with(
            'success',
            'Line sync dimulai di background. Halaman ini akan refresh otomatis sampai selesai.',
        );
    }
}
