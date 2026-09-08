import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Cog6ToothIcon, Cog8ToothIcon, EnvelopeIcon, KeyIcon, UserIcon, UserGroupIcon } from '@heroicons/react/16/solid';
import { Head, useForm } from '@inertiajs/react';

export default function Register({roles, departments, jabatans, status}) {
    const {data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        jabatan_id: '',
        department_id: '',
        password_confirmation: '',
        role: '',
    });
    const submit = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthenticatedLayout>
            <div className="-mt-6 px-4  w-full h-lvh flex items-center justify-center ">
                <div className="relative  z-10 backdrop-blur-md flex flex-col items-center rounded-xl bg-lightTheme py-12 min-w-[21rem] md:w-96 px-5 shadow-md">
                    <div className="absolute -top-10 left-1/2 -translate-x-1/2 bg-transparent w-20 h-20 rounded-full mb-2">
                        <img src={"storage/logo/logo.png"} alt="Logo" />
                        {/* <img src="{{ url($transaction->image_path) }}" alt=""> */}
                    </div>

                    <h2 className='text-greenTheme text-4xl font-playfairDisplay font-semibold mb-6'>Create New User</h2>
                    <Head title="Log in" />
                    {status && (
                        <div className="mb-4 text-sm font-medium text-green-600">
                            {status}
                        </div>
                    )}
                    {/* <div className="bg-blue-400 inline-block w-full">dasdas</div> */}

                    <form className=' inline-block w-full ' onSubmit={submit}>
                        <div className='flex gap-2 items-center px-3 py-1 mt-4 w-full rounded-xl shadow-md bg-opacity-15 !bg-white'>
                            {/* <InputLabel htmlFor="email" value="Email" /> */}
                            <InputLabel htmlFor="name" value="">
                                <UserIcon className='w-6 text-blackTheme' />
                            </InputLabel>
                            <TextInput
                                id="name"
                                type="text"
                                name="name"
                                value={data.name}
                                className="bg-transparent w-full outline-none border-none"
                                placeholder="name"
                                autoComplete="username"
                                isFocused={true}
                                onChange={(e) => setData('name', e.target.value)}
                            />
                            <InputError message={errors.name} className="mt-2" />
                        </div>
                        <div className='flex gap-2 items-center px-3 py-1 mt-4 w-full rounded-xl shadow-md bg-opacity-15 !bg-white'>
                            {/* <InputLabel htmlFor="email" value="Email" /> */}
                            <InputLabel htmlFor="email" value="">
                                <EnvelopeIcon className='w-6 text-blackTheme' />
                            </InputLabel>
                            <TextInput
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                className="bg-transparent  w-full outline-none border-none"
                                placeholder="email"
                                autoComplete="username"
                                isFocused={true}
                                onChange={(e) => setData('email', e.target.value)}
                            />

                        </div>
                        <InputError message={errors.email} className="mt-2" />

                        <div className=" flex gap-2 items-center px-3 mt-5 w-full rounded-xl py-1 shadow-md bg-opacity-15 !bg-white">
                            <InputLabel htmlFor="password" value="">
                                <KeyIcon className='w-6 text-blackTheme' />
                            </InputLabel>

                            <TextInput
                                id="password"
                                type="password"
                                name="password"
                                placeholder="password"
                                value={data.password}
                                className=" outline-none border-none bg-transparent  w-full focus:outline-none"
                                autoComplete="current-password"
                                onChange={(e) => setData('password', e.target.value)}
                            />

                        </div>
                        <div className=" flex gap-2 items-center px-3 mt-5 w-full rounded-xl py-1 shadow-md bg-opacity-15 !bg-white">
                            <InputLabel htmlFor="password_confirmation" value="">
                                <KeyIcon className='w-6 text-blackTheme' />
                            </InputLabel>

                            <TextInput
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                placeholder="password_confirmation"
                                value={data.password_confirmation}
                                className=" outline-none border-none bg-transparent  w-full focus:outline-none"
                                autoComplete="current-password_confirmation"
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                            />

                        </div>
                        <div className='flex gap-2 items-center px-3 py-1 mt-4 w-full rounded-xl shadow-md bg-opacity-15 !bg-white'>
                            <InputLabel htmlFor="jabatan_id" value="">
                                <UserGroupIcon className='w-6 text-blackTheme' />
                            </InputLabel>
                            <SelectInput
                                id="jabatan_id"
                                name="jabatan_id"
                                value={data.jabatan_id}
                                className="outline-none italic border-none bg-transparent w-full focus:outline-none"
                                onChange={(e) => setData('jabatan_id', e.target.value)}
                            >
                                <option value="">Select Jabatan</option>
                                {
                                    jabatans.map(jabatan=>(
                                        <option key={jabatan.id} value={jabatan.id}>{jabatan.name}</option>
                                    ))
                                }
                            </SelectInput>
                            <InputError message={errors.jabatan_id} className="mt-2" />
                        </div>
                        <div className='flex gap-2 items-center px-3 py-1 mt-4 w-full rounded-xl shadow-md bg-opacity-15 !bg-white'>
                            <InputLabel htmlFor="department_id" value="">
                                <Cog6ToothIcon className='w-6 text-blackTheme' />
                            </InputLabel>
                            <SelectInput
                                id="department_id"
                                name="department_id"
                                value={data.department_id}
                                className="outline-none italic border-none bg-transparent w-full focus:outline-none"
                                onChange={(e) => setData('department_id', e.target.value)}
                            >
                                <option value="">Select Department</option>
                                {
                                    departments.map(department=>(
                                        <option key={department.id} value={department.id}>{department.name}</option>
                                    ))
                                }
                            </SelectInput>
                            <InputError message={errors.department_id} className="mt-2" />
                        </div>
                        <div className=" flex gap-2 items-center px-3 mt-5 w-full rounded-xl py-1 shadow-md bg-opacity-15 !bg-white">
                            <InputLabel htmlFor="role" value="">
                                <Cog8ToothIcon className='w-6 text-blackTheme' />
                            </InputLabel>

                            <SelectInput
                                id="role"
                                type="text"
                                name="role"
                                placeholder="Select Role"
                                value={data.role}
                                className=" outline-none italic border-none bg-transparent  w-full focus:outline-none"
                                autoComplete="current-role"
                                onChange={(e) => setData('role', e.target.value)}
                            >
                                <option value="">Select Role</option>
                                {
                                    roles.map(role=>(
                                        <option key={role.id} value={role.id}>{role.name}</option>
                                    ))
                                }
                            </SelectInput>

                        </div>

                        <InputError message={errors.password} className="mt-2" />
                        <InputError message={errors.password_confirmation} className="mt-2" />

                        <PrimaryButton className=" mt-5 w-full px-4 py-2 text-center bg-orangeTheme !text-base tracking-wider" disabled={processing}>
                            Create
                        </PrimaryButton>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}