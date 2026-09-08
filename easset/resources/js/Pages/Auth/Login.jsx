import ApplicationLogo from '@/Components/ApplicationLogo';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { EnvelopeIcon, KeyIcon } from "@heroicons/react/16/solid";

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (

        <GuestLayout>
            <div className="-mt-6 px-4  w-full h-lvh flex items-center justify-center ">
                <div className="relative  z-10 backdrop-blur-md flex flex-col items-center rounded-xl bg-lightTheme py-12 min-w-[21rem] px-5 shadow-md">
                    <div className="absolute -top-10 left-1/2 -translate-x-1/2 bg-transparent w-20 h-20 rounded-full mb-2">
                        <img src={"storage/logo/logo.png"} alt="Logo" />
                    </div>

                    <h2 className='text-greenTheme text-4xl font-playfairDisplay font-semibold mb-6'>Login</h2>
                    <Head title="Log in" />
                    {status && (
                        <div className="mb-4 text-sm font-medium text-green-600">
                            {status}
                        </div>
                    )}

                    <form className=' inline-block w-full ' onSubmit={submit}>
                        <div className='flex gap-2 items-center px-3 py-1 mt-4 w-full rounded-xl shadow-md bg-opacity-15 !bg-white'>
                            <InputLabel htmlFor="email" value="">
                                <EnvelopeIcon className='w-6 text-blackTheme' />
                            </InputLabel>
                            <TextInput
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                className="bg-transparent outline-none border-none w-full"
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
                                className=" outline-none border-none bg-transparent focus:outline-none w-full"
                                autoComplete="current-password"
                                onChange={(e) => setData('password', e.target.value)}
                            />

                        </div>
                        <InputError message={errors.password} className="mt-2" />

                        <div className="mt-5 block">
                            <label className="flex items-center">
                                <Checkbox
                                    name="remember"
                                    checked={data.remember}
                                    onChange={(e) =>
                                        setData('remember', e.target.checked)
                                    }
                                />
                                <span className="ms-2 text-sm text-gray-600 dark:text-gray-400">
                                    Remember me
                                </span>
                            </label>
                        </div>
                        <PrimaryButton className=" mt-2 w-full px-4 py-1 text-center bg-orangeTheme !text-base tracking-wider" disabled={processing}>
                            Login
                        </PrimaryButton>
                    </form>
                </div>
            </div>
        </GuestLayout>
    );
}
