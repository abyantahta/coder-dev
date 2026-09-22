export default function EmptyState({ title, description }) {
    return (
        <div className="animate-fade-up rounded-panel border border-dashed border-line bg-canvas-soft px-6 py-12 text-center">
            <h3 className="font-display text-sm font-semibold text-ink">{title}</h3>
            {description && (
                <p className="mx-auto mt-2 max-w-md text-sm text-ink-muted">{description}</p>
            )}
        </div>
    );
}
