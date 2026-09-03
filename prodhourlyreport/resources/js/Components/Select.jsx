import { forwardRef } from 'react';

export default forwardRef(function Select({ className = '', children, ...props }, ref) {
    return (
        <select
            {...props}
            ref={ref}
            className={
                'rounded-lg border-gray-300 px-3 py-2.5 text-base shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-gray-100 disabled:text-gray-400 ' +
                className
            }
        >
            {children}
        </select>
    );
});
