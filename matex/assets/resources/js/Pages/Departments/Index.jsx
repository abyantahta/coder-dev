import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import Modal from "@/Components/Modal";
import Pagination from "@/Components/Pagination";
import PrimaryButton from "@/Components/PrimaryButton";
import SecondaryButton from "@/Components/SecondaryButton";
import TableHeading from "@/Components/TableHeading";
import ZebraCell from "@/Components/Table/ZebraCell";
import TextInput from "@/Components/TextInput";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import useQueryParams from "@/hooks/useQueryParams";
import { PencilIcon, PlusIcon, XMarkIcon } from "@heroicons/react/16/solid";
import { Head, router, useForm } from "@inertiajs/react";
import { useState } from "react";

export default function Index({ departments, queryParams = null, success, error }) {
    const { queryParams: params, searchFieldChanged, sortChanged, onKeyPress } = useQueryParams(
        "departments.index",
        queryParams
    );

    const [showCreateModal, setShowCreateModal] = useState(false);
    const [editingDepartment, setEditingDepartment] = useState(null);

    const createForm = useForm({ name: "" });
    const editForm = useForm({ name: "" });

    const openCreateModal = () => {
        createForm.reset();
        createForm.clearErrors();
        setShowCreateModal(true);
    };

    const submitCreate = (e) => {
        e.preventDefault();
        createForm.post(route("departments.store"), {
            onSuccess: () => setShowCreateModal(false),
        });
    };

    const openEditModal = (department) => {
        editForm.setData("name", department.name);
        editForm.clearErrors();
        setEditingDepartment(department);
    };

    const submitEdit = (e) => {
        e.preventDefault();
        editForm.put(route("departments.update", editingDepartment.id), {
            onSuccess: () => setEditingDepartment(null),
        });
    };

    const deleteDepartment = (department) => {
        if (!window.confirm(`Delete department "${department.name}"?`)) {
            return;
        }
        router.delete(route("departments.destroy", department.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Master - Department
                </h2>
            }
        >
            <Head title="Department" />
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
                                    placeholder="Search department"
                                    onBlur={(e) =>
                                        searchFieldChanged("name", e.target.value)
                                    }
                                    onKeyPress={(e) => onKeyPress("name", e)}
                                />
                                <button
                                    type="button"
                                    onClick={openCreateModal}
                                    className="bg-greenTheme rounded-md text-white font-bold tracking-wider px-4 py-2 flex items-center justify-center gap-1 hover:brightness-110 duration-150"
                                >
                                    Add Department
                                    <PlusIcon className="w-5" />
                                </button>
                            </div>
                            <div className="overflow-auto">
                                <table className="mb-4 min-w-full table-fixed z-10 h-full border-collapse border-spacing-2 gap-1">
                                    <thead className="overflow-auto">
                                        <tr className="min-w-full flex text-center gap-3 !font-semibold">
                                            <TableHeading
                                                name="id"
                                                sort_field={params.sort_field}
                                                sort_direction={params.sort_direction}
                                                sortChanged={sortChanged}
                                                className="bg-greenTheme text-white w-20 rounded-[0.25rem] h-11 flex items-center !font-semibold justify-center"
                                            >
                                                No
                                            </TableHeading>
                                            <TableHeading
                                                name="name"
                                                sort_field={params.sort_field}
                                                sort_direction={params.sort_direction}
                                                sortChanged={sortChanged}
                                                className="bg-greenTheme text-white grow rounded-[0.25rem] h-11 flex items-center !font-semibold justify-center"
                                            >
                                                Department
                                            </TableHeading>
                                            <th className="bg-greenTheme text-white w-36 rounded-[0.25rem] h-11 flex items-center !font-semibold justify-center">
                                                Aksi
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="overflow-auto box-border no-scrollbar">
                                        {departments.data.map((department, index) => (
                                            <tr className="min-w-full flex text-center gap-3 mt-3" key={department.id}>
                                                <ZebraCell index={index} className="px-1 h-11 py-2 text-ellipsis overflow-hidden text-nowrap text-center border-greenTheme border-2 rounded-[0.25rem] w-20">
                                                    {(departments.meta.current_page - 1) *
                                                        departments.meta.per_page +
                                                        (index + 1)}
                                                </ZebraCell>
                                                <ZebraCell index={index} className="px-3 h-11 py-2 text-ellipsis overflow-hidden text-nowrap grow text-center border-greenTheme border-2 rounded-[0.25rem]">
                                                    {department.name}
                                                </ZebraCell>
                                                <ZebraCell index={index} className="px-6 flex h-11 py-1 text-ellipsis overflow-hidden text-nowrap text-center border-greenTheme border-2 rounded-[0.25rem] w-36">
                                                    <button
                                                        onClick={() => openEditModal(department)}
                                                        className="bg-yellow-500 hover:brightness-110 duration-150 p-2 mx-auto w-fit font-bold text-white rounded-md flex items-center justify-center"
                                                    >
                                                        <PencilIcon className="w-5" />
                                                    </button>
                                                    <button
                                                        onClick={() => deleteDepartment(department)}
                                                        className="bg-red-400 hover:brightness-125 duration-150 p-2 mx-auto w-fit font-bold text-white rounded-md flex items-center justify-center"
                                                    >
                                                        <XMarkIcon className="w-5" />
                                                    </button>
                                                </ZebraCell>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            <Pagination links={departments.meta.links} />
                        </div>
                    </div>
                </div>
            </div>

            <Modal show={showCreateModal} onClose={() => setShowCreateModal(false)}>
                <form onSubmit={submitCreate} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Add Department
                    </h2>
                    <div className="mt-4">
                        <InputLabel htmlFor="name" value="Nama Department" />
                        <TextInput
                            id="name"
                            className="mt-1 block w-full"
                            value={createForm.data.name}
                            onChange={(e) => createForm.setData("name", e.target.value)}
                            isFocused
                        />
                        <InputError message={createForm.errors.name} className="mt-2" />
                    </div>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setShowCreateModal(false)}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton className="px-4 py-2 bg-greenTheme" disabled={createForm.processing}>
                            Save
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={!!editingDepartment} onClose={() => setEditingDepartment(null)}>
                <form onSubmit={submitEdit} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Edit Department
                    </h2>
                    <div className="mt-4">
                        <InputLabel htmlFor="edit_name" value="Nama Department" />
                        <TextInput
                            id="edit_name"
                            className="mt-1 block w-full"
                            value={editForm.data.name}
                            onChange={(e) => editForm.setData("name", e.target.value)}
                            isFocused
                        />
                        <InputError message={editForm.errors.name} className="mt-2" />
                    </div>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setEditingDepartment(null)}>
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
