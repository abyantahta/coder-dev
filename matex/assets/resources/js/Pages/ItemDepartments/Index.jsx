import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import { ArrowDownTrayIcon, ArrowUpTrayIcon } from "@heroicons/react/16/solid";
import { useRef } from "react";

export default function Index({ totalItems, itemsWithoutDepartment, success, error }) {
    const fileInputRef = useRef(null);
    const importForm = useForm({ file: null });

    const onFileChosen = (e) => {
        const file = e.target.files[0];
        if (!file) return;
        importForm.setData("file", file);
        importForm.post(route("items.import.departments"), {
            forceFormData: true,
            onFinish: () => {
                e.target.value = "";
            },
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Master - Kategorisasi Department Item
                </h2>
            }
        >
            <Head title="Kategorisasi Department Item" />
            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-lightTheme dark:bg-gray-800 shadow-lg sm:rounded-lg">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            {success && (
                                <div className="mb-4 rounded-md bg-green-100 px-4 py-2 text-green-800">
                                    {success}
                                </div>
                            )}
                            {error && (
                                <div className="mb-4 rounded-md bg-red-100 px-4 py-2 text-red-800">
                                    {error}
                                </div>
                            )}

                            <div className="mb-6 grid grid-cols-2 gap-4">
                                <div className="rounded-md bg-green-100 px-4 py-3 text-center">
                                    <div className="text-3xl font-bold text-green-800">
                                        {Intl.NumberFormat("en-DE").format(totalItems)}
                                    </div>
                                    <div className="text-sm text-green-800">Total Item</div>
                                </div>
                                <div className="rounded-md bg-red-100 px-4 py-3 text-center">
                                    <div className="text-3xl font-bold text-red-800">
                                        {Intl.NumberFormat("en-DE").format(itemsWithoutDepartment)}
                                    </div>
                                    <div className="text-sm text-red-800">Belum Ada Department</div>
                                </div>
                            </div>

                            <div className="text-sm text-gray-700 dark:text-gray-300 space-y-2 mb-6">
                                <p>
                                    Karena department item tidak otomatis terisi dari sync WSA,
                                    kategorisasi dilakukan manual lewat Excel:
                                </p>
                                <ol className="list-decimal list-inside space-y-1">
                                    <li>Klik <b>Export ke Excel</b> untuk mengunduh daftar seluruh item beserta department saat ini.</li>
                                    <li>Isi kolom <b>department</b> di file Excel tersebut (nama department harus sama persis dengan yang ada di Master Department).</li>
                                    <li>Klik <b>Import dari Excel</b> dan unggah file yang sudah diisi. Item akan dicocokkan lewat nomor asetnya.</li>
                                </ol>
                                <p>
                                    Boleh dicicil — kalau baru sempat isi sebagian, baris yang kolom
                                    department-nya masih kosong akan otomatis dilewati (tidak dianggap
                                    error) dan tidak diubah. Export lagi kapan saja untuk lihat sisa
                                    item yang belum ada department-nya (lihat angka di atas), lalu
                                    lanjutkan isi & import bertahap sampai selesai.
                                </p>
                            </div>

                            <div className="flex flex-col sm:flex-row gap-4">
                                <a
                                    href={route("items.export.departments")}
                                    className="w-full sm:w-64 py-3 px-4 tracking-wide text-center bg-brownTheme font-bold flex items-center justify-center gap-2 text-white rounded-md hover:brightness-110 duration-150"
                                >
                                    <ArrowDownTrayIcon className="w-6" />
                                    Export ke Excel
                                </a>
                                <button
                                    type="button"
                                    onClick={() => fileInputRef.current?.click()}
                                    disabled={importForm.processing}
                                    className="w-full sm:w-64 py-3 px-4 tracking-wide text-center bg-greenTheme font-bold flex items-center justify-center gap-2 text-white rounded-md hover:brightness-110 duration-150 disabled:opacity-50"
                                >
                                    <ArrowUpTrayIcon className="w-6" />
                                    {importForm.processing ? "Mengimpor..." : "Import dari Excel"}
                                </button>
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept=".xlsx,.xls,.csv"
                                    className="hidden"
                                    onChange={onFileChosen}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
