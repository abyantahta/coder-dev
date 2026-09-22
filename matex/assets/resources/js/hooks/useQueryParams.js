import { router } from "@inertiajs/react";

export default function useQueryParams(routeName, initialQueryParams, { resetPageOnFilter = false } = {}) {
    const queryParams = initialQueryParams || {};

    const searchFieldChanged = (name, value) => {
        if (value) {
            queryParams[name] = value;
            if (resetPageOnFilter) {
                queryParams["page"] = 1;
            }
        } else {
            delete queryParams[name];
        }
        router.get(route(routeName), queryParams);
    };

    const sortChanged = (name) => {
        if (name === queryParams.sort_field) {
            queryParams.sort_direction = queryParams.sort_direction === "asc" ? "desc" : "asc";
        } else {
            queryParams.sort_field = name;
            queryParams.sort_direction = "asc";
        }
        router.get(route(routeName), queryParams);
    };

    const onKeyPress = (name, e) => {
        if (e.key !== "Enter") return;
        searchFieldChanged(name, e.target.value);
    };

    return { queryParams, searchFieldChanged, sortChanged, onKeyPress };
}
