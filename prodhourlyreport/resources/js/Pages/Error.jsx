import ApplicationLogo from '@/Components/ApplicationLogo';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, usePage } from '@inertiajs/react';

const MESSAGES = {
    403: {
        title: "You don't have access to this page",
        body: 'Your account role doesn’t include this action. If you think that’s wrong, ask your Unit Head to check your account.',
    },
    404: {
        title: 'Page not found',
        body: "The page you're looking for doesn't exist or may have moved.",
    },
    419: {
        title: 'Your session expired',
        body: 'For your security, please log in again to continue.',
    },
    500: {
        title: 'Something went wrong',
        body: 'An unexpected error occurred on our end. Please try again in a moment.',
    },
    503: {
        title: 'Down for maintenance',
        body: "We're making some improvements. Please check back shortly.",
    },
};

export default function Error({ status }) {
    const user = usePage().props.auth?.user;
    const { title, body } = MESSAGES[status] ?? MESSAGES[500];

    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-10">
            <Head title={`${status} — ${title}`} />

            <ApplicationLogo className="h-12 w-12 fill-current text-brand-600" />

            <p className="mt-6 text-sm font-semibold uppercase tracking-wide text-brand-600">Error {status}</p>
            <h1 className="mt-2 text-2xl font-semibold text-gray-900">{title}</h1>
            <p className="mt-2 max-w-sm text-center text-sm text-gray-500">{body}</p>

            <Link href={user ? route('dashboard') : route('login')} className="mt-8">
                <PrimaryButton>{user ? 'Back to Dashboard' : 'Go to Login'}</PrimaryButton>
            </Link>
        </div>
    );
}
