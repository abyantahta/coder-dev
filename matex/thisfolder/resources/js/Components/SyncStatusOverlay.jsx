import { CheckBadgeIcon, XCircleIcon } from "@heroicons/react/16/solid";

export default function SyncStatusOverlay({ loading, status, message, onClose }) {
    return (
        <>
            {loading && (
                <div className="w-full h-lvh bg-[rgba(0,0,0,0.3)]  fixed top-0 left-0 z-[99999]"></div>
            )}
            {status && (
                <div className="w-full h-lvh bg-[rgba(0,0,0,0.3)] fixed top-0 left-0 z-[99999]">
                    <div className="w-fit relative top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white h-60 flex flex-col items-center px-12 py-8 rounded-lg">
                        <div
                            onClick={onClose}
                            className="cursor-pointer w-12 bg-white rounded-full text-red-500 absolute -top-4 -right-2"
                        >
                            <XCircleIcon className="" />
                        </div>
                        {status === "success" ? (
                            <>
                                <CheckBadgeIcon className="w-28 text-green-600" />
                                <h2 className="font-bold text-3xl">{message}</h2>
                            </>
                        ) : (
                            <>
                                <XCircleIcon className="w-28 text-red-400" />
                                <h2 className=" mt-2 font-bold text-3xl">{message}</h2>
                            </>
                        )}
                    </div>
                </div>
            )}
        </>
    );
}
