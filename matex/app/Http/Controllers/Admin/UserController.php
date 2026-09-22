<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Concerns\ResolvesQadSupplier;
use App\Enums\CompanyType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Company;
use App\Models\QadSupplier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use ResolvesQadSupplier;

    public function index(Request $request): Response
    {
        $actingUser = $request->user();

        $users = User::query()
            ->with('company')
            ->when(
                ! $actingUser->hasRole('admin'),
                fn ($q) => $q->where('role', '!=', UserRole::Admin->value)
            )
            ->when($request->string('search')->isNotEmpty(), function ($q) use ($request) {
                $search = '%'.$request->string('search').'%';
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->when($request->string('role')->isNotEmpty(), fn ($q) => $q->where('role', $request->string('role')))
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->integer('company_id')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'role' => $request->string('role')->toString(),
                'company_id' => $request->integer('company_id') ?: null,
            ],
            'roles' => $this->roleOptions($actingUser),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'user' => null,
            'prefill' => [
                'role' => $request->string('role')->toString(),
                'supplier_code' => $request->string('supplier_code')->toString(),
            ],
            'qadSuppliers' => $this->qadSupplierOptions(),
            'roles' => $this->roleOptions($request->user()),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'company_id' => $this->resolveCompanyId($data['role'], $data['supplier_code'] ?? null),
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(Request $request, User $user): Response
    {
        $this->authorizeManaging($request, $user);

        return Inertia::render('Admin/Users/Form', [
            'user' => $user->load('company'),
            'prefill' => null,
            'qadSuppliers' => $this->qadSupplierOptions(),
            'roles' => $this->roleOptions($request->user()),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'company_id' => $this->resolveCompanyId($data['role'], $data['supplier_code'] ?? null),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $this->authorizeManaging($request, $user);

        if ($user->hasRole(UserRole::Admin) && User::where('role', UserRole::Admin)->count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus admin terakhir.');
        }

        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }

    /**
     * Purchasing can't touch an existing Admin account, even one it
     * couldn't have created — checked again here (not just in the
     * FormRequest) so edit()/destroy() can't be reached by URL either.
     */
    private function authorizeManaging(Request $request, User $user): void
    {
        abort_if(
            ! $request->user()->hasRole('admin') && $user->hasRole(UserRole::Admin),
            403,
            'Hanya Admin yang dapat mengelola akun Admin.'
        );
    }

    /**
     * Non-Admin (Bertindak sebagai Purchasing) can't create/see Admin
     * accounts at all — Admin stays exclusive to Admin.
     */
    private function roleOptions(User $actingUser): array
    {
        $roles = $actingUser->hasRole('admin')
            ? UserRole::cases()
            : array_filter(UserRole::cases(), fn (UserRole $r) => $r !== UserRole::Admin);

        return collect($roles)->values()->map(fn (UserRole $r) => [
            'value' => $r->value,
            'label' => $r->label(),
        ])->all();
    }

    /**
     * QAD supplier master, categorized only — the picker used when role is
     * Supplier RM/OHP (see resolveCompanyId below for how it becomes a
     * companies row).
     */
    private function qadSupplierOptions()
    {
        return QadSupplier::active()
            ->whereIn('category', [CompanyType::RawMat->value, CompanyType::Ohp->value])
            ->orderBy('name')
            ->get(['id', 'qad_code', 'name', 'city', 'category']);
    }

    /**
     * Supplier roles get their company from the QAD supplier picker
     * (auto-provisioned via ResolvesQadSupplier, same as PO creation);
     * internal roles (Admin/Purchasing/PPIC) are attached to the one
     * internal SDI company.
     */
    private function resolveCompanyId(string $role, ?string $supplierCode): ?int
    {
        if (in_array($role, [UserRole::SupplierRm->value, UserRole::SupplierOhp->value], true)) {
            return $this->resolveSupplierFromQadCode($supplierCode)->id;
        }

        return Company::where('type', CompanyType::Sdi->value)->value('id');
    }
}
