<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJabatanRequest;
use App\Http\Requests\UpdateJabatanRequest;
use App\Http\Resources\JabatanResource;
use App\Models\Jabatan;

class JabatanController extends Controller
{
    public function index()
    {
        $query = Jabatan::query();
        $sortField = request("sort_field", "name");
        $sortDirection = request("sort_direction", "asc");

        if (request("name")) {
            $query->where("name", "like", "%" . request("name") . "%");
        }

        $jabatans = $query->orderBy($sortField, $sortDirection)->paginate(10)->withQueryString();

        return inertia("Jabatans/Index", [
            "jabatans" => JabatanResource::collection($jabatans),
            "queryParams" => request()->query() ?: null,
            "success" => session('success'),
            "error" => session('error'),
        ]);
    }

    public function store(StoreJabatanRequest $request)
    {
        Jabatan::create($request->validated());

        return to_route('jabatans.index')->with('success', 'Jabatan was created');
    }

    public function update(UpdateJabatanRequest $request, Jabatan $jabatan)
    {
        $jabatan->update($request->validated());

        return to_route('jabatans.index')->with('success', 'Jabatan was updated');
    }

    public function destroy(Jabatan $jabatan)
    {
        if ($jabatan->users()->exists()) {
            return to_route('jabatans.index')->with('error', 'This jabatan is still assigned to users and cannot be deleted');
        }

        $jabatan->delete();

        return to_route('jabatans.index')->with('success', 'Jabatan was deleted');
    }
}
