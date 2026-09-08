import SelectInput from "@/Components/SelectInput";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import useQueryParams from "@/hooks/useQueryParams";
import {
    buildCategoryPieOptions,
    buildMonthlyBarOptions,
    buildRadialSTOOptions,
} from "@/Pages/Dashboard/charts";
import { Head } from "@inertiajs/react";
import Chart from "react-apexcharts";

export default function Dashboard({
    years,
    numberOfItems,
    numberOfActiveItems,
    numberOfDeactiveItems,
    itemsByCategories,
    queryParams = null,
    penambahan_aset_monthly,
    disposal_aset_monthly,
    months_label,
    depreciationByMonths,
    sto_progress,
}) {
    const { queryParams: params, searchFieldChanged } = useQueryParams("dashboard", queryParams);

    const itemsByCategoriesOptions = buildCategoryPieOptions(itemsByCategories);
    const serviceDateByMonth = buildMonthlyBarOptions(months_label, "Penambahan Aset", penambahan_aset_monthly, "M");
    const disposalDateByMonth = buildMonthlyBarOptions(months_label, "Disposal Aset", disposal_aset_monthly, " JT", true);
    const depreciationByMonthOptions = buildMonthlyBarOptions(depreciationByMonths.label, "Depresiasi Aset", depreciationByMonths.data, "M", true);
    const stoProgressOptions = buildRadialSTOOptions(sto_progress);

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-5">
                <div className="mx-auto max-w-[100rem] sm:px-6 lg:px-8">
                    <div className="shadow-sm sm:rounded-lg ">
                        <div className="p-6 text-gray-900">
                            <div className="flex md:flex-row flex-col gap-y-3 gap-x-8 ">
                                <SelectInput
                                    className="md:w-72 border-gray-700 w-full border-[3px] italic font-semibold focus:none ring:none text-greenTheme"
                                    defaultValue={params.category_id}
                                    name="category_id"
                                    onChange={(e) =>
                                        searchFieldChanged("category_id", e.target.value)
                                    }
                                >
                                    <option value="">All</option>
                                    <option value="1">Tooling</option>
                                    <option value="2">Building</option>
                                    <option value="3">Vehicle</option>
                                    <option value="4">Office Equipment</option>
                                    <option value="5">Machine</option>
                                </SelectInput>
                                <SelectInput
                                    className="w-full md:w-72 border-gray-700 border-[3px] italic font-semibold focus:none ring:none text-greenTheme"
                                    defaultValue={params.years}
                                    name="years"
                                    onChange={(e) =>
                                        searchFieldChanged("years", e.target.value)
                                    }
                                >
                                    <option value="">All Year</option>
                                    {years.map((year) => (
                                        <option key={year} value={year}>
                                            {year}
                                        </option>
                                    ))}
                                </SelectInput>
                            </div>
                            <div className="w-full gap-4 flex md:flex-row flex-col mt-6">
                                <div className="flex flex-col gap-4  min-w-80 justify-between ">
                                    <div className="w-full flex flex-col bg-greenTheme text-white text-center h-32 rounded-xl justify-center">
                                        <h2 className="tracking-wider text-2xl">
                                            JUMLAH ASSET
                                        </h2>
                                        <h3 className="font-semibold text-6xl">
                                            {Intl.NumberFormat("en-DE").format(numberOfItems)}
                                        </h3>
                                    </div>
                                    <div className="flex flex-col gap-2 h-56 w-full">
                                        <div className="h-[50%] gap-2 flex flex-col">
                                            <h2 className="bg-green-100 h-1/2 flex items-center justify-center font-bold text-xl rounded-t-lg text-green-800">
                                                Active : {numberOfActiveItems}
                                            </h2>
                                            <h2 className="bg-red-100 h-1/2 flex items-center justify-center font-bold text-xl text-red-700">
                                                Deactive : {numberOfDeactiveItems}
                                            </h2>
                                        </div>
                                        <Chart
                                            options={stoProgressOptions.options}
                                            series={stoProgressOptions.series}
                                            type="radialBar"
                                            className="bg-indigo-50 rounded-b-lg "
                                            height={"70%"}
                                        />
                                    </div>
                                    <div className="rounded-md py-3 shadow-md bg-green-200 w-full px-4">
                                        <h4 className="font-semibold text-center text-xl uppercase mb-2 text-brownTheme">
                                            Jenis aset aktif
                                        </h4>
                                        <Chart
                                            options={itemsByCategoriesOptions}
                                            series={itemsByCategoriesOptions.series}
                                            labels={itemsByCategoriesOptions.labels}
                                            type="pie"
                                            height="200"
                                        />
                                    </div>
                                </div>
                                <div className="w-full shrink flex box-border h-full flex-col gap-3 ">
                                    <div className="bg-pink-100 rounded-md pt-3 shadow-md grow h-1/3">
                                        <h4 className="font-semibold text-center text-xl uppercase mb-2 text-brownTheme">
                                            DEPRESIASI ASET
                                        </h4>
                                        <Chart
                                            options={depreciationByMonthOptions.options}
                                            series={depreciationByMonthOptions.series}
                                            type="bar"
                                            height="88%"
                                        />
                                    </div>
                                    <div className="bg-red-100 rounded-lg pt-2 shadow-md grow h-1/3 pb-4 ">
                                        <h4 className="font-semibold text-center text-xl uppercase mb-2 text-brownTheme">
                                            PENAMBAHAN ASET
                                        </h4>
                                        <Chart
                                            options={serviceDateByMonth.options}
                                            series={serviceDateByMonth.series}
                                            type="bar"
                                            height="88%"
                                        />
                                    </div>
                                    <div className="bg-pink-100 rounded-md pt-3 shadow-md grow h-1/3 ">
                                        <h4 className="font-semibold text-center text-xl uppercase mb-2 text-brownTheme">
                                            DISPOSAL ASET
                                        </h4>
                                        <Chart
                                            options={disposalDateByMonth.options}
                                            series={disposalDateByMonth.series}
                                            type="bar"
                                            height="88%"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
