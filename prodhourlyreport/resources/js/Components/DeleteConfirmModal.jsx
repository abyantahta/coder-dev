import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';

export default function DeleteConfirmModal({ show, label, description, onClose, onConfirm }) {
    return (
        <Modal show={show} onClose={onClose}>
            <div className="p-6">
                <h2 className="text-lg font-semibold text-gray-900">Delete "{label}"?</h2>
                <p className="mt-1 text-sm text-gray-500">{description ?? "This can't be undone."}</p>
                <div className="mt-6 flex justify-end gap-3">
                    <SecondaryButton onClick={onClose}>Cancel</SecondaryButton>
                    <DangerButton onClick={onConfirm}>Delete</DangerButton>
                </div>
            </div>
        </Modal>
    );
}
