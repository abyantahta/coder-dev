import { Link } from "@inertiajs/react";
import { ChevronDownIcon } from "@heroicons/react/16/solid";

export default function HoverDropdown({ label, active = false, items }) {
    return (
        <div className="group relative inline-flex items-center">
            <button
                type="button"
                className={
                    "inline-flex items-center gap-1 border-b-2 px-1 text-greenTheme pt-1 text-lg font-medium leading-5 transition duration-150 ease-in-out focus:outline-none " +
                    (active
                        ? "border-greenTheme !font-bold focus:border-green-400 dark:border-indigo-600 dark:text-gray-100"
                        : "border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 focus:border-gray-300 focus:text-gray-700 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:text-gray-300 dark:focus:border-gray-700 dark:focus:text-gray-300")
                }
            >
                {label}
                <ChevronDownIcon className="h-4 w-4" />
            </button>
            <div className="invisible absolute left-0 top-full z-50 w-48 pt-2 opacity-0 transition duration-150 ease-in-out group-hover:visible group-hover:opacity-100">
                <div className="rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-gray-700">
                    {items.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-800 dark:focus:bg-gray-800"
                        >
                            {item.label}
                        </Link>
                    ))}
                </div>
            </div>
        </div>
    );
}
