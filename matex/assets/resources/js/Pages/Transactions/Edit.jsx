import InputError from '@/Components/InputError'
import InputLabel from '@/Components/InputLabel'
import TextInput from '@/Components/TextInput'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, useForm } from '@inertiajs/react'
import SelectInput from '@/Components/SelectInput'

const Edit = ({ auth, transaction,users,locations }) => {
    let { image_path, kondisi, location_id, pic, created_at, updated_by, updated_at, keterangan} = transaction.data
    let { id,no_asset, name } = transaction.data.item_id
    let category = transaction.data.item_id.category_id.name
    let createdBy = transaction.data.created_by
    const { data, setData, post, errors } = useForm({
        item_id: id || "",
        image_path: "",
        location_id: location_id.id || "",
        kondisi: kondisi || "",
        keterangan: keterangan || "",
        pic: pic.id || "",
        created_by: createdBy.id || "",
        updated_by : auth.user.id,
        _method: "PUT"
    })
    const onSubmit = (e) => {
        e.preventDefault();
        post(route('transactions.update', transaction.data.id));
    }
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        STO - {name}
                    </h2>
                </div>
            }
        >
            <Head title="STO FORM" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900 dark:text-gray-100">

                            <form onSubmit={onSubmit} action="" className='p-4 sm:p-8 bg-lightTheme dark:bg-gray-800 sm:rounded-lg '>
                                <div className="mt-4">
                                    <InputLabel htmlFor="item_id" value="Nomor Asset" />
                                    <TextInput
                                        id="item_id"
                                        type="text"
                                        name="item_id"
                                        isFocused={true}
                                        defaultValue={no_asset}
                                        className="mt-1 block w-full outline-none border-none bg-lightTheme text-gray-400 shadow-[-3px_4px_14px_-5px_rgba(0,_0,_0,_0.1)] text-3xl text-center   h-16" />
                                    <InputError message={errors.item_id} className='mt-2' />
                                </div>
                                <div className="flex flex-col md:flex-row w-full gap-x-8">
                                    <div className="mt-4 md:w-1/2 w-full">
                                        <InputLabel htmlFor="name" value="Nama Asset" />
                                        <TextInput
                                            id="name"
                                            type="text"
                                            name="name"
                                            isFocused={true}
                                            defaultValue={name}
                                            disabled
                                            className="mt-1 block w-full outline-none border-none bg-lightTheme text-gray-400 shadow-[-3px_4px_14px_-5px_rgba(0,_0,_0,_0.1)] text-xl   h-12" />
                                        <InputError message={errors.name} className='mt-2' />
                                    </div>
                                    <div className="mt-4 md:w-1/2 w-full">
                                        <InputLabel htmlFor="kategori" value="Kategori" />
                                        <TextInput
                                            id="kategori"
                                            type="text"
                                            name="kategori"
                                            isFocused={true}
                                            defaultValue={category}
                                            disabled
                                            className="mt-1 block w-full outline-none border-none bg-lightTheme text-gray-400 shadow-[-3px_4px_14px_-5px_rgba(0,_0,_0,_0.1)] text-xl   h-12" />
                                        <InputError message={errors.kategori} className='mt-2' />
                                    </div>
                                </div>


                                <div className="flex flex-col md:flex-row w-full gap-x-8">
                                    <div className="mt-4 md:w-1/2 w-full">
                                        <InputLabel htmlFor="lokasi" value="Lokasi" />
                                        <SelectInput
                                            className="w-full outline-none border-none bg-[#FFFEF5] shadow-[-3px_4px_14px_-5px_rgba(0,_0,_0,_0.1)] text-xl  !text-greenTheme  h-12"
                                            onChange={(e) =>
                                                setData("location_id", e.target.value)
                                            }
                                            value={data.location_id}
                                        >
                                            <option value="">Select Lokasi</option>
                                            {
                                                locations.map(location=>(
                                                    <option key={location.location_name} value={location.id}>{location.location_name}</option>
                                                ))
                                            }
                                        </SelectInput>
                                        <InputError message={errors.lokasi} className='mt-2' />
                                    </div>
                                    <div className="mt-4 md:w-1/2 w-full">
                                        <InputLabel htmlFor="pic" value="PIC" />
                                        <SelectInput
                                            className="w-full outline-none border-none bg-[#FFFEF5] shadow-[-3px_4px_14px_-5px_rgba(0,_0,_0,_0.1)] text-xl  !text-greenTheme  h-12"
                                            onChange={(e) =>
                                                setData("pic", e.target.value)
                                            }
                                            value={data.pic}
                                        >
                                            <option value="">Select PIC</option>
                                            {
                                                users.map(user=>(
                                                    <option key={user.id} value={user.id}>{user.name}</option>
                                                ))
                                            }
                                        </SelectInput>
                                        <InputError message={errors.pic} className='mt-2' />
                                    </div>
                                </div>
                                <div className="flex flex-col md:flex-row w-full gap-x-8">
                                    <div className="mt-4 md:w-1/2 w-full">
                                        <InputLabel htmlFor="kondisi" value="Kondisi" />
                                        <SelectInput
                                            className="w-full outline-none border-none bg-[#FFFEF5] shadow-[-3px_4px_14px_-5px_rgba(0,_0,_0,_0.1)] text-xl  !text-greenTheme  h-12"
                                            onChange={(e) =>
                                                setData("kondisi", e.target.value)
                                            }
                                            value={data.kondisi}
                                        >
                                            <option value="">Select Kondisi</option>
                                            <option value="baik">Baik</option>
                                            <option value="Baik (tidak digunakan)">Baik (tidak digunakan)</option>
                                            <option value="Hilang atau Rusak">Hilang atau Rusak</option>
                                        </SelectInput>
                                        <InputError message={errors.kondisi} className='mt-2' />
                                    </div>
                                    
                                <div className="mt-4 md:w-1/2 w-full">
                                    <InputLabel htmlFor="keterangan" value="Keterangan" />
                                    <TextInput
                                        id="keterangan"
                                        type="text"
                                        name="keterangan"
                                        isFocused={true}
                                        value={data.keterangan}
                                        className="mt-1 block w-full outline-none border-none bg-[#FFFEF5] shadow-[-3px_4px_14px_-5px_rgba(0,_0,_0,_0.1)] text-xl  !text-greenTheme  h-12"
                                        onChange={e => setData("keterangan", e.target.value)} />
                                    <InputError message={errors.keterangan} className='mt-2' />
                                </div>


                                </div>
                                    <div className="mt-4">
                                        <InputLabel htmlFor="project_image_path" value="Project Image" />
                                        <TextInput
                                            id="project_image_path"
                                            type="file"
                                            name="image_path"
                                            className="mt-1 block w-full outline-none border-none bg-[#FFFEF5] shadow-[-3px_4px_14px_-5px_rgba(0,_0,_0,_0.1)] text-xl  !text-greenTheme  h-12 file:bg-brownTheme file:border-none file:h-full file:text-white file:px-4 file:text-base file:mr-4"
                                            onChange={e => setData('image_path',
                                                e.target.files[0])} />
                                        <InputError message={errors.image_path} className='mt-2' />
                                    </div>
                                {image_path && (
                                    <div className="mb-4 mt-4 rounded-md overflow-hidden">
                                        <img src={image_path} className="h-60" />
                                    </div>
                                )}

                                <div className="mt-4">
                                    <h2 className='font-bold text-center'>STO by {createdBy.name}
                                        at {new Date(created_at).toLocaleString()}
                                    </h2>
                                </div>
                                {updated_by && (
                                <div className="mt-4">
                                    <h2 className='font-bold text-center'>STO updated by {updated_by.name}
                                        at {new Date(updated_at).toLocaleString()}
                                    </h2>
                                </div>
                                )}
                                
                                <div className="mt-4 text-center flex flex-col-reverse md:flex-row gap-x-4 gap-y-3 justify-center ">
                                    <Link href={route("items.index")} className='bg-gray-100 py-3 px-20 font-semibold text-greenTheme rounded shadow transition-all hover:bg-gray-200text-md'>
                                        Cancel
                                    </Link>
                                    <button type='submit' className='bg-orangeTheme font-semibold py-3 text-md px-20 text-white rounded shadow transition-all hover:brightness-110'>
                                        Submit
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    )
}

export default Edit