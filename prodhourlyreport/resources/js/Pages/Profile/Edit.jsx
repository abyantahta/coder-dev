import PageHeader from '@/Components/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status }) {
    return (
        <AuthenticatedLayout>
            <Head title="Profile" />

            <PageHeader title="Profile" description="Update your account details and password." />

            <div className="mx-auto max-w-2xl space-y-6">
                <div className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
                    <UpdateProfileInformationForm mustVerifyEmail={mustVerifyEmail} status={status} />
                </div>

                <div className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
                    <UpdatePasswordForm />
                </div>

                <div className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
                    <DeleteUserForm />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
