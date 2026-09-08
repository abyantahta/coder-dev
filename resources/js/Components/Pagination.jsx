import { Link } from '@inertiajs/react'
import React from 'react'

const Pagination = ({ links }) => {
    return (
        <nav className="text-center mt-4">
            {links.map(link => (
                <Link
                    preserveScroll
                    key={link.label}
                    href={link.url || ""}
                    className={
                        `inline-block py-2 px-3 rounded-lg text-gray-500 text-xs ${link.active ? "bg-greenTheme !text-gray-200" : ""} ${!link.url ? "!text-gray-200 cursor-not-allowed" : "hover:bg-greenTheme hover:text-gray-200"}`
                    } dangerouslySetInnerHTML={{ __html: link.label }}>
                    {/* {link.label} */}
                </Link>
            ))}
        </nav>
    )
}

export default Pagination