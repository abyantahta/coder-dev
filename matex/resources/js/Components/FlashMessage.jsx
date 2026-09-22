import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function FlashMessage() {
    const { flash } = usePage().props;
    const [visible, setVisible] = useState(false);
    const message = flash?.success || flash?.error;
    const isError = Boolean(flash?.error);

    useEffect(() => {
        if (message) {
            setVisible(true);
            const timer = setTimeout(() => setVisible(false), 4500);
            return () => clearTimeout(timer);
        }
    }, [message]);

    if (!visible || !message) {
        return null;
    }

    return (
        <div className="ui-page !py-3">
            <div
                className={`animate-flash-in rounded-panel border px-4 py-3 text-sm font-medium shadow-panel ${
                    isError
                        ? 'border-rose-200 bg-rose-50 text-rose-800'
                        : 'border-brand-line bg-brand-muted text-brand-deep'
                }`}
            >
                {message}
            </div>
        </div>
    );
}
