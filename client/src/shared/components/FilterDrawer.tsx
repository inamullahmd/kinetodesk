import type { ReactNode } from 'react'
import { X } from 'lucide-react'

type FilterDrawerProps = {
    open: boolean
    title?: string
    activeCount?: number
    children: ReactNode
    onClose: () => void
    onReset: () => void
    onApply?: () => void
    variant: 'light' | 'dark'
}

export default function FilterDrawer({
    open,
    title = 'Filters',
    activeCount = 0,
    children,
    onClose,
    onReset,
    onApply,
    variant,
}: FilterDrawerProps) {
    const isDark = variant === 'dark'

    if (!open) return null

    return (
        <div className="fixed inset-0 z-[80]">
            <button
                type="button"
                aria-label="Close filters"
                className="absolute inset-0 bg-slate-950/40 backdrop-blur-[1px]"
                onClick={onClose}
            />

            <aside
                className={[
                    'absolute right-0 top-0 flex h-full w-full max-w-md flex-col border-l shadow-2xl',
                    isDark
                        ? 'border-slate-800 bg-slate-950 text-slate-100 shadow-black/40'
                        : 'border-slate-200 bg-white text-slate-950 shadow-slate-300/50',
                ].join(' ')}
            >
                <div
                    className={[
                        'flex items-start justify-between gap-4 border-b px-5 py-4',
                        isDark ? 'border-slate-800' : 'border-slate-200',
                    ].join(' ')}
                >
                    <div>
                        <div className="flex items-center gap-2">
                            <h2 className="text-lg font-semibold">{title}</h2>

                            {activeCount > 0 ? (
                                <span
                                    className={[
                                        'inline-flex h-6 min-w-6 items-center justify-center rounded-full px-2 text-xs font-bold',
                                        isDark
                                            ? 'bg-blue-500/15 text-blue-300'
                                            : 'bg-blue-50 text-blue-700',
                                    ].join(' ')}
                                >
                                    {activeCount}
                                </span>
                            ) : null}
                        </div>

                        <p
                            className={[
                                'mt-1 text-sm',
                                isDark ? 'text-slate-400' : 'text-slate-500',
                            ].join(' ')}
                        >
                            Refine the table results.
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        className={[
                            'rounded-full p-2 transition',
                            isDark
                                ? 'text-slate-400 hover:bg-slate-900 hover:text-white'
                                : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900',
                        ].join(' ')}
                        aria-label="Close filter drawer"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                <div className="flex-1 overflow-y-auto px-5 py-5">
                    <div className="space-y-5">{children}</div>
                </div>

                <div
                    className={[
                        'flex items-center justify-between gap-3 border-t px-5 py-4',
                        isDark ? 'border-slate-800' : 'border-slate-200',
                    ].join(' ')}
                >
                    <button
                        type="button"
                        onClick={onReset}
                        className={[
                            'h-10 rounded-xl border px-4 text-sm font-semibold transition',
                            isDark
                                ? 'border-slate-700 bg-slate-950 text-slate-200 hover:bg-slate-900'
                                : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
                        ].join(' ')}
                    >
                        Reset
                    </button>

                    <button
                        type="button"
                        onClick={onApply ?? onClose}
                        className={[
                            'h-10 rounded-xl px-5 text-sm font-semibold transition',
                            isDark
                                ? 'bg-blue-600 text-white hover:bg-blue-500'
                                : 'bg-slate-950 text-white hover:bg-slate-800',
                        ].join(' ')}
                    >
                        Apply filters
                    </button>
                </div>
            </aside>
        </div>
    )
}