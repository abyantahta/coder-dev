import Checkbox from '@/Components/Checkbox';
import CrudModal from '@/Components/CrudModal';
import CrudTable from '@/Components/CrudTable';
import DeleteConfirmModal from '@/Components/DeleteConfirmModal';
import FilterBar, { FilterSearch, FilterSelect } from '@/Components/FilterBar';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import Select from '@/Components/Select';
import StatusBadge from '@/Components/StatusBadge';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useCrudResource } from '@/lib/useCrudResource';
import { useServerFilters } from '@/lib/useServerFilters';
import { roleLabel } from '@/utils/roles';
import { Head } from '@inertiajs/react';
import { useMemo } from 'react';

function emptyForm() {
    return { name: '', email: '', password: '', role: 'leader', is_active: true, line_ids: [] };
}

function toFormData(user) {
    return {
        name: user.name,
        email: user.email,
        password: '',
        role: user.role,
        is_active: user.is_active,
        line_ids: user.lines.map((line) => line.id),
    };
}

export default function Index({ users, lines, roleOptions, filters: initialFilters = {} }) {
    const { filters, setFilter, setSearch } = useServerFilters('users.index', initialFilters, {
        only: ['users', 'filters'],
    });
    const {
        data,
        setData,
        errors,
        processing,
        editing,
        confirmingDelete,
        setConfirmingDelete,
        openCreate,
        openEdit,
        close,
        submit,
        destroy,
    } = useCrudResource({ routeName: 'users', emptyForm, toFormData });

    const toggleLine = (lineId) => {
        setData(
            'line_ids',
            data.line_ids.includes(lineId)
                ? data.line_ids.filter((id) => id !== lineId)
                : [...data.line_ids, lineId],
        );
    };

    const showLineAssignment = data.role === 'leader' || data.role === 'group_head';
    const lineOptions = useMemo(
        () => lines.map((line) => ({ value: String(line.id), label: line.name })),
        [lines],
    );
    const roleFilterOptions = useMemo(
        () => roleOptions.map((option) => ({ value: option.value, label: option.label })),
        [roleOptions],
    );

    return (
        <AuthenticatedLayout>
            <Head title="Users" />

            <PageHeader
                title="Users"
                description="Accounts and roles. Assign lines to leaders and group heads; Unit Head automatically covers all lines."
                action={<PrimaryButton onClick={openCreate}>Add User</PrimaryButton>}
            />

            <FilterBar>
                <FilterSearch value={filters.search ?? ''} onChange={setSearch} placeholder="Name or email…" />
                <FilterSelect
                    label="Role"
                    value={filters.role ?? ''}
                    onChange={(value) => setFilter('role', value)}
                    options={roleFilterOptions}
                    emptyLabel="All roles"
                />
                <FilterSelect
                    label="Line"
                    value={filters.line_id ?? ''}
                    onChange={(value) => setFilter('line_id', value)}
                    options={lineOptions}
                    emptyLabel="All lines"
                />
            </FilterBar>

            <div className="mb-2 text-sm text-gray-500">{users.length} user(s)</div>

            <CrudTable
                headers={['Name', 'Email', 'Role', 'Lines', 'Status']}
                rows={users}
                emptyMessage="No users match these filters."
                onEdit={openEdit}
                onDelete={setConfirmingDelete}
                renderCells={(user) => (
                    <>
                        <td className="px-4 py-3 font-medium text-gray-900">{user.name}</td>
                        <td className="px-4 py-3 text-gray-500">{user.email}</td>
                        <td className="px-4 py-3 text-gray-500">{roleLabel(user.role)}</td>
                        <td className="px-4 py-3 text-gray-500">
                            {user.lines.map((l) => l.name).join(', ') || '—'}
                        </td>
                        <td className="px-4 py-3"><StatusBadge active={user.is_active} /></td>
                    </>
                )}
            />

            <CrudModal
                show={editing !== null}
                title={editing?.id ? 'Edit User' : 'Add User'}
                onClose={close}
                onSubmit={submit}
                processing={processing}
            >
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="name" value="Name" />
                        <TextInput id="name" className="mt-1 block w-full" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        <InputError message={errors.name} className="mt-2" />
                    </div>
                    <div>
                        <InputLabel htmlFor="email" value="Email" />
                        <TextInput id="email" type="email" className="mt-1 block w-full" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                        <InputError message={errors.email} className="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel htmlFor="password" value={editing?.id ? 'Password (leave blank to keep current)' : 'Password'} />
                    <TextInput id="password" type="password" className="mt-1 block w-full" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="role" value="Role" />
                    <Select id="role" className="mt-1 block w-full" value={data.role} onChange={(e) => setData('role', e.target.value)}>
                        {roleOptions.map((option) => (
                            <option key={option.value} value={option.value}>{option.label}</option>
                        ))}
                    </Select>
                    <InputError message={errors.role} className="mt-2" />
                </div>

                {showLineAssignment && (
                    <div>
                        <InputLabel value="Assigned Lines" />
                        <div className="mt-1 grid grid-cols-2 gap-2 rounded-lg border border-gray-200 p-3">
                            {lines.map((line) => (
                                <label key={line.id} className="flex items-center gap-2 text-sm text-gray-600">
                                    <Checkbox checked={data.line_ids.includes(line.id)} onChange={() => toggleLine(line.id)} />
                                    {line.name}
                                </label>
                            ))}
                        </div>
                        <InputError message={errors.line_ids} className="mt-2" />
                    </div>
                )}

                <label className="flex items-center gap-2">
                    <Checkbox checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
                    <span className="text-sm text-gray-600">Active</span>
                </label>
            </CrudModal>

            <DeleteConfirmModal
                show={confirmingDelete !== null}
                label={confirmingDelete?.name}
                onClose={() => setConfirmingDelete(null)}
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
