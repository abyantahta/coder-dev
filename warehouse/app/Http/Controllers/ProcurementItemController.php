<?php

namespace App\Http\Controllers;

use App\Imports\ProcurementItemImport;
use App\Models\ItemCodePrefix;
use App\Models\ProcurementCategory;
use App\Models\ProcurementItem;
use App\Models\ProcurementRequestDetail;
use App\Models\Uom;
use App\Services\BudgetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcurementItemController extends Controller
{
    public function __construct(private BudgetService $budgetService) {}

    public function index()
    {
        $items = ProcurementItem::with(['uom', 'category'])->orderBy('item_code')->paginate(20);
        return view('procurement.master.items.index', compact('items'));
    }

    public function create()
    {
        $uoms = Uom::where('is_active', true)->orderBy('code')->get();
        $categories = ProcurementCategory::where('is_active', true)->orderBy('name')->get();
        $prefixes = ItemCodePrefix::where('is_active', true)->orderBy('code')->get();
        $nextItemCode = $prefixes->isNotEmpty() ? ProcurementItem::generateItemCode($prefixes->first()->code) : null;
        return view('procurement.master.items.form', [
            'item' => new ProcurementItem(), 'uoms' => $uoms, 'categories' => $categories,
            'prefixes' => $prefixes, 'nextItemCode' => $nextItemCode,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:200',
            'uom_id'      => 'required|exists:uoms,id',
            'category_id' => 'nullable|exists:procurement_categories,id',
            'prefix'      => 'required|string|exists:item_code_prefixes,code',
            'price'       => 'nullable|numeric|min:0',
            'photo'       => 'nullable|image|max:2048',
        ]);

        // Kode item digenerate ulang di sini (bukan percaya input form) supaya
        // tetap benar walau form sempat dibuka lama & item lain sudah ditambah.
        $data = $request->only('name', 'uom_id', 'category_id', 'description', 'price') + [
            'is_active' => true,
            'item_code' => ProcurementItem::generateItemCode($request->prefix),
        ];

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->storeSquarePhoto($request->file('photo'));
        }

        ProcurementItem::create($data);
        return redirect()->route('procurement.items.index')->with('success', "Item ATK {$data['item_code']} berhasil ditambahkan.");
    }

    public function edit(ProcurementItem $procurementItem)
    {
        $uoms = Uom::where('is_active', true)->orderBy('code')->get();
        $categories = ProcurementCategory::where('is_active', true)->orderBy('name')->get();
        return view('procurement.master.items.form', ['item' => $procurementItem, 'uoms' => $uoms, 'categories' => $categories]);
    }

    public function update(Request $request, ProcurementItem $procurementItem)
    {
        $request->validate([
            'name'        => 'required|string|max:200',
            'uom_id'      => 'required|exists:uoms,id',
            'category_id' => 'nullable|exists:procurement_categories,id',
            'price'       => 'nullable|numeric|min:0',
            'photo'       => 'nullable|image|max:2048',
        ]);

        $data = $request->only('name', 'uom_id', 'category_id', 'description', 'price') + [
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('photo')) {
            if ($procurementItem->photo) Storage::disk('public')->delete($procurementItem->photo);
            $data['photo'] = $this->storeSquarePhoto($request->file('photo'));
        }

        $procurementItem->update($data);
        return redirect()->route('procurement.items.index')->with('success', 'Item ATK berhasil diperbarui.');
    }

    /**
     * Crop foto ke persegi (center crop dari sisi terpendek) & resize ke
     * resolusi standar 600x600, supaya semua foto item ATK konsisten —
     * berapa pun rasio/ukuran aslinya. Disimpan sebagai JPEG kualitas 85.
     */
    private function storeSquarePhoto(\Illuminate\Http\UploadedFile $file): string
    {
        [$width, $height] = getimagesize($file->getRealPath());

        $source = match ($file->getMimeType()) {
            'image/png'  => imagecreatefrompng($file->getRealPath()),
            'image/webp' => imagecreatefromwebp($file->getRealPath()),
            'image/gif'  => imagecreatefromgif($file->getRealPath()),
            default      => imagecreatefromjpeg($file->getRealPath()),
        };

        $side  = min($width, $height);
        $srcX  = (int) (($width - $side) / 2);
        $srcY  = (int) (($height - $side) / 2);

        $target = 600;
        $dest = imagecreatetruecolor($target, $target);
        // Latar putih dulu (buat PNG transparan supaya nggak jadi hitam pas di-JPEG-kan).
        $white = imagecolorallocate($dest, 255, 255, 255);
        imagefill($dest, 0, 0, $white);

        imagecopyresampled($dest, $source, 0, 0, $srcX, $srcY, $target, $target, $side, $side);

        $filename = 'photos/procurement/' . uniqid('item_', true) . '.jpg';
        $tmpPath = tempnam(sys_get_temp_dir(), 'sqphoto');
        imagejpeg($dest, $tmpPath, 85);

        imagedestroy($source);
        imagedestroy($dest);

        Storage::disk('public')->put($filename, file_get_contents($tmpPath));
        unlink($tmpPath);

        return $filename;
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:5120']);

        $import = new ProcurementItemImport();
        Excel::import($import, $request->file('file'));

        return back()->with('success', "Import selesai: {$import->imported} item berhasil, {$import->skipped} dilewati.");
    }

    public function downloadTemplate()
    {
        $headers = ['kode_item', 'nama_item', 'uom', 'kategori'];
        $sample  = [
            ['ATK-001', 'Pulpen Ballpoint', 'PCS', 'Alat Tulis'],
            ['ATK-002', 'Kertas HVS A4',   'RIM', 'Kertas'],
        ];

        $callback = function () use ($headers, $sample) {
            $f = fopen('php://output', 'w');
            fputcsv($f, $headers);
            foreach ($sample as $row) fputcsv($f, $row);
            fclose($f);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_item_atk.csv"',
        ]);
    }

    public function search(Request $request)
    {
        $query = ProcurementItem::with(['uom', 'category'])->where('is_active', true);

        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->q . '%')
                  ->orWhere('item_code', 'like', '%' . $request->q . '%');
            });
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->whereHas('category', fn($q) => $q->where('name', $request->category));
        }

        $items = $query->orderBy('name')->limit(100)->get();
        $departmentId = auth()->user()->department_id;

        // Berapa kali user ini sendiri pernah minta tiap item — dipakai buat
        // naikkan item yang "sering diorder" ke depan katalog (personal per akun).
        $orderCounts = ProcurementRequestDetail::whereHas('request', fn($q) => $q->where('user_id', auth()->id()))
            ->selectRaw('item_id, COUNT(*) as cnt')
            ->groupBy('item_id')
            ->pluck('cnt', 'item_id');

        $mapped = $items->map(function ($i) use ($departmentId, $orderCounts) {
            $remaining = $departmentId ? $this->budgetService->remaining($departmentId, $i->id) : null;

            return [
                'id'          => $i->id,
                'item_code'   => $i->item_code,
                'name'        => $i->name,
                'description' => $i->description,
                'uom_id'      => $i->uom_id,
                'uom_code'    => $i->uom?->code,
                'category_id' => $i->category_id,
                'category'    => $i->category?->name ?? 'Lainnya',
                'photo'       => $i->photo ? asset('storage/' . $i->photo) : null,
                'price'       => $i->price ?? 0,
                'remaining_budget' => $remaining,
                'order_count' => (int) ($orderCounts[$i->id] ?? 0),
            ];
        });

        // Sering diorder duluan (turun), sisanya tetap alfabetis.
        $sorted = $mapped->sortByDesc('order_count')->values();

        return response()->json($sorted);
    }

    /** Tree kategori (parent + sub-kategori) buat filter di layar "Buat Permintaan". */
    public function categories()
    {
        $parents = ProcurementCategory::with('children')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($parents->map(fn($p) => [
            'id'       => $p->id,
            'name'     => $p->name,
            'children' => $p->children->map(fn($c) => ['id' => $c->id, 'name' => $c->name]),
        ]));
    }
}
