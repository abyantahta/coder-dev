import { forwardRef, useEffect, useImperativeHandle, useRef } from 'react';

export default forwardRef(function SelectInput(
    { type = 'text', className = '', children, ...props },
    ref,
) {
    const localRef = useRef(null);

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    return (
        <select
            {...props}
            className={
                'rounded-md border-gray-300 shadow-sm cursor-pointer focus:border-greenTheme focus:ring-0  ' +
                className
            }
            ref={localRef}
        >
            {children}
        </select>
    );
});
