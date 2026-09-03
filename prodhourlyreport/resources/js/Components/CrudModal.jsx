import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function CrudModal({ show, title, onClose, onSubmit, processing, children }) {
    return (
        <Modal show={show} onClose={onClose}>
            <form onSubmit={onSubmit} className="p-6">
                <h2 className="text-lg font-semibold text-gray-900">{title}</h2>
                <div className="mt-4 space-y-4">{children}</div>
                <div className="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" onClick={onClose}>Cancel</SecondaryButton>
                    <PrimaryButton disabled={processing}>Save</PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}
