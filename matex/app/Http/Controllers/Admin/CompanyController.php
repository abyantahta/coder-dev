<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyRequest;
use App\Http\Requests\Admin\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(Request $request): Response
    {
        $companies = Company::query()
            ->withCount('users')
            ->when($request->string('search')->isNotEmpty(), function ($q) use ($request) {
                $search = '%'.$request->string('search').'%';
                $q->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', $search)
                        ->orWhere('name', 'like', $search);
                });
            })
            ->when($request->string('type')->isNotEmpty(), fn ($q) => $q->where('type', $request->string('type')))
            ->orderBy('type')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Companies/Index', [
            'companies' => $companies,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'type' => $request->string('type')->toString(),
            ],
            'types' => collect(CompanyType::cases())->map(fn ($t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Companies/Form', [
            'company' => null,
            'types' => collect(CompanyType::cases())->map(fn ($t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ]),
        ]);
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        Company::create($request->validated());

        return redirect()
            ->route('admin.companies.index')
            ->with('success', 'Company berhasil ditambahkan.');
    }

    public function edit(Company $company): Response
    {
        return Inertia::render('Admin/Companies/Form', [
            'company' => $company,
            'types' => collect(CompanyType::cases())->map(fn ($t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ]),
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->validated());

        return redirect()
            ->route('admin.companies.index')
            ->with('success', 'Company berhasil diperbarui.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        if ($company->users()->exists()) {
            return back()->with('error', 'Company masih memiliki user. Hapus/pindahkan user terlebih dahulu.');
        }

        $hasPoAsRm = \App\Models\PurchaseOrder::query()
            ->where('supplier_rm_id', $company->id)
            ->exists();

        $hasPoAsOhp = \App\Models\DeliverySchedule::query()
            ->where('ohp_supplier_id', $company->id)
            ->exists();

        $hasDefaultItem = $company->defaultItems()->exists();

        if ($hasPoAsRm || $hasPoAsOhp || $hasDefaultItem) {
            return back()->with('error', 'Company masih terpakai di master item / Purchase Order. Nonaktifkan saja.');
        }

        $company->delete();

        return back()->with('success', 'Company berhasil dihapus.');
    }
}
