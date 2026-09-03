import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';

/**
 * Encapsulates the create/edit/delete lifecycle shared by every master-data
 * admin page: which record is being edited (if any), the confirm-delete
 * dialog, and wiring the form to the resource's store/update/destroy routes.
 * Page-specific field logic (selects, checkboxes, etc.) stays in the page.
 */
export function useCrudResource({ routeName, emptyForm, toFormData }) {
    const [editing, setEditing] = useState(null); // null = closed, {} = create, {...row} = edit
    const [confirmingDelete, setConfirmingDelete] = useState(null);
    const form = useForm(emptyForm());
    const { data, setData, errors, processing } = form;

    const openCreate = () => {
        form.setData(emptyForm());
        form.clearErrors();
        setEditing({});
    };

    const openEdit = (row) => {
        form.setData(toFormData(row));
        form.clearErrors();
        setEditing(row);
    };

    const close = () => {
        setEditing(null);
        form.reset();
    };

    const submit = (e) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: close };

        if (editing?.id) {
            form.put(route(`${routeName}.update`, editing.id), options);
        } else {
            form.post(route(`${routeName}.store`), options);
        }
    };

    const destroy = () => {
        router.delete(route(`${routeName}.destroy`, confirmingDelete.id), {
            preserveScroll: true,
            onSuccess: () => setConfirmingDelete(null),
        });
    };

    return {
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
    };
}
