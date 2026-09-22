import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import Modal from "@/Components/Modal";
import Pagination from "@/Components/Pagination";
import PrimaryButton from "@/Components/PrimaryButton";
import SecondaryButton from "@/Components/SecondaryButton";
import SelectInput from "@/Components/SelectInput";
import TableHeading from "@/Components/TableHeading";
import ZebraCell from "@/Components/Table/ZebraCell";
import TextInput from "@/Components/TextInput";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import useQueryParams from "@/hooks/useQueryParams";
import { PencilIcon, PlusIcon, XMarkIcon } from "@heroicons/react/16/solid";
import { Head, Link, router, useForm } from "@inertiajs/react";
import { useState } from "react";

export default function Index({ auth, users, roles, departments, jabatans, queryParams = null, success, error }) {
    const { queryParams: params, searchFieldChanged, sortChanged, onKeyPress } = useQueryParams(
        "users.index",
        queryParams
    );

    const [editingUser, setEditingUser] = useState(null);
    const editForm = useForm({ role_id: "", department_id: "", jabatan_id: "" });

    const openEditModal = (user) => {
        editForm.setData({
            role_id: user.role_id ?? "",
            department_id: user.department_id ?? "",
            jabatan_id: user.jabatan_id ?? "",
        });
        editForm.clearErrors();
        setEditingUser(user);
    };

    const submitEdit = (e) => {
        e.preventDefault();
        editForm.put(route("users.update", editingUser.id), {
            onSuccess: () => setEditingUser(null),
        });
    };

    const deleteUser = (user) => {
        if (!window.confirm(`Delete user "${user.name}"?`)) {
            return;
        }
        router.delete(route("users.destroy", user.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Master - Users
                </h2>
            }
        >
            <Head title="Users" />
            <div className="py-12">
                <div className="max-w-[90rem] mx-auto sm:px-6 lg:px-8">
                    <div className="bg-lightTheme dark:bg-gray-800 shadow-lg sm:rounded-lg">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            {success && (
                                <div className="mb-4 rounded-md bg-green-100 px-4 py-2 text-green-800">
                                    {success}
                                </div>
                            )}
                            {error && (
                                <div className="mb-4 rounded-md bg-red-100 px-4 py-2 text-red-800">
                                    {error}
                                </div>
                            )}
                            <div className="mb-4 flex flex-col gap-y-2 sm:flex-row sm:justify-between">
                                <TextInput
                                    className="w-full sm:w-72 border-gray-700 border-[3px] placeholder:italic text-greenTheme font-normal focus:border-greenTheme focus:ring-greenTheme placeholder:text-greenTheme"
                                    defaultValue={params.name}
                                    placeholder="Search user"
                                    onBlur={(e) => searchFieldChanged("name", e.target.value)}
                                    onKeyPress={(e) => onKeyPress("name", e)}
                                />
                                <Link
                                    href={route("register")}
                                    className="bg-greenTheme rounded-md text-white font-bold tracking-wider px-4 py-2 flex items-center justify-center gap-1 hover:brightness-110 duration-150"
                                >
                                    Add User
                                    <PlusIcon className="w-5" />
                                </Link>
                            </div>
                            <div className="overflow-auto">
                                <table className="mb-4 min-w-full table-fixed z-10 h-full border-collapse border-spacing-2 gap-1">
                                    <thead className="overflow-auto">
                                        <tr className="min-w-full flex text-center gap-3 !font-semibold">
                                            <TableHeading
                                                name="name"
                                                sort_field={params.sort_field}
                                                sort_direction={params.sort_direction}
                                                sortChanged={sortChanged}
                                                className="bg-greenTheme text-white w-52 rounded-[0.25rem] h-11 flex items-center !font-semibold justify-center"
                                            >
                                                Name
                                            </TableHeading>
                                            <TableHeading
                                                name="email"
                                                sort_field={params.sort_field}
                                                sort_direction={params.sort_direction}
                                                sortChanged={sortChanged}
                                                className="bg-greenTheme text-white grow rounded-[0.25rem] h-11 flex items-center !font-semibold justify-center"
                                            >
                                                Email
                                            </TableHeading>
                                            <th className="bg-greenTheme text-white w-40 rounded-[0.25rem] h-11 flex items-center !font-semibold justify-center">
                                                Jabatan
                                            </th>
                                            <th className="bg-greenTheme text-white w-40 rounded-[0.25rem] h-11 flex items-center !font-semibold justify-center">
                                                Department
                                            </th>
                                            <th className="bg-greenTheme text-white w-36 rounded-[0.25rem] h-11 flex items-center !font-semibold justify-center">
                                                Role
                                            </th>
                                            <th className="bg-greenTheme text-white w-36 rounded-[0.25rem] h-11 flex items-center !font-semibold justify-center">
                                                Aksi
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="overflow-auto box-border no-scrollbar">
                                        {users.data.map((user, index) => (
                                            <tr className="min-w-full flex text-center gap-3 mt-3" key={user.id}>
                                                <ZebraCell index={index} className="px-3 h-11 py-2 text-ellipsis overflow-hidden text-nowrap text-center border-greenTheme border-2 rounded-[0.25rem] w-52">
                                                    {user.name}
                                                </ZebraCell>
                                                <ZebraCell index={index} className="px-3 h-11 py-2 text-ellipsis overflow-hidden text-nowrap grow text-center border-greenTheme border-2 rounded-[0.25rem]">
                                                    {user.email}
                                                </ZebraCell>
                                                <ZebraCell index={index} className="px-3 h-11 py-2 text-ellipsis overflow-hidden text-nowrap text-center border-greenTheme border-2 rounded-[0.25rem] w-40">
                                                    {user.jabatan?.name}
                                                </ZebraCell>
                                                <ZebraCell index={index} className="px-3 h-11 py-2 text-ellipsis overflow-hidden text-nowrap text-center border-greenTheme border-2 rounded-[0.25rem] w-40">
                                                    {user.department?.name}
                                                </ZebraCell>
                                                <ZebraCell index={index} className="px-3 h-11 py-2 text-ellipsis overflow-hidden text-nowrap text-center border-greenTheme border-2 rounded-[0.25rem] w-36">
                                                    {user.role?.name}
                                                </ZebraCell>
                                                <ZebraCell index={index} className="px-6 flex h-11 py-1 text-ellipsis overflow-hidden text-nowrap text-center border-greenTheme border-2 rounded-[0.25rem] w-36">
                                                    <button
                                                        onClick={() => openEditModal(user)}
                                                        className="bg-yellow-500 hover:brightness-110 duration-150 p-2 mx-auto w-fit font-bold text-white rounded-md flex items-center justify-center"
                                                    >
                                                        <PencilIcon className="w-5" />
                                                    </button>
                                                    {user.id !== auth.user.id && (
                                                        <button
                                                            onClick={() => deleteUser(user)}
                                                            className="bg-red-400 hover:brightness-125 duration-150 p-2 mx-auto w-fit font-bold text-white rounded-md flex items-center justify-center"
                                                        >
                                                            <XMarkIcon className="w-5" />
                                                        </button>
                                                    )}
                                                </ZebraCell>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            <Pagination links={users.meta.links} />
                        </div>
                    </div>
                </div>
            </div>

            <Modal show={!!editingUser} onClose={() => setEditingUser(null)}>
                <form onSubmit={submitEdit} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Edit User {editingUser?.name}
                    </h2>
                    <div className="mt-4">
                        <InputLabel htmlFor="role_id" value="Role" />
                        <SelectInput
                            id="role_id"
                            className="mt-1 block w-full"
                            value={editForm.data.role_id}
                            onChange={(e) => editForm.setData("role_id", e.target.value)}
                        >
                            <option value="">Select Role</option>
                            {roles.map((role) => (
                                <option key={role.id} value={role.id}>
                                    {role.name}
                                </option>
                            ))}
                        </SelectInput>
                        <InputError message={editForm.errors.role_id} className="mt-2" />
                    </div>
                    <div className="mt-4">
                        <InputLabel htmlFor="jabatan_id" value="Jabatan" />
                        <SelectInput
                            id="jabatan_id"
                            className="mt-1 block w-full"
                            value={editForm.data.jabatan_id}
                            onChange={(e) => editForm.setData("jabatan_id", e.target.value)}
                        >
                            <option value="">Select Jabatan</option>
                            {jabatans.map((jabatan) => (
                                <option key={jabatan.id} value={jabatan.id}>
                                    {jabatan.name}
                                </option>
                            ))}
                        </SelectInput>
                        <InputError message={editForm.errors.jabatan_id} className="mt-2" />
                    </div>
                    <div className="mt-4">
                        <InputLabel htmlFor="department_id" value="Department" />
                        <SelectInput
                            id="department_id"
                            className="mt-1 block w-full"
                            value={editForm.data.department_id}
                            onChange={(e) => editForm.setData("department_id", e.target.value)}
                        >
                            <option value="">Select Department</option>
                            {departments.map((department) => (
                                <option key={department.id} value={department.id}>
                                    {department.name}
                                </option>
                            ))}
                        </SelectInput>
                        <InputError message={editForm.errors.department_id} className="mt-2" />
                    </div>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setEditingUser(null)}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton className="px-4 py-2 bg-greenTheme" disabled={editForm.processing}>
                            Save
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
