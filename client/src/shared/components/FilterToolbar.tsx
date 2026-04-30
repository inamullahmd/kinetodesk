import type { ReactNode } from 'react'
import { RotateCcw, Search, SlidersHorizontal } from 'lucide-react'

type FilterToolbarProps = {
    search: string
    onSearchChange: (value: string) => void
    searchPlaceholder: string
    activeCount: number
    onOpenFilters: () => void
    onReset: () => void
    variant: 'light' | 'dark'
    children?: ReactNode
}

export default function FilterToolbar({
    search,
    onSearchChange,
    searchPlaceholder,
    activeCount,
    onOpenFilters,
    onReset,
    variant,
    children,
}: FilterToolbarProps) {
    const isDark = variant === 'dark'
    const hasSearch = search.trim().length > 0
    const hasActiveFilters = activeCount > 0 || hasSearch

    const inputClass = [
        'h-11 w-full rounded-2xl border pl-10 pr-3 text-sm outline-none transition',
        isDark
            ? 'border-slate-800 bg-slate-950 text-slate-100 placeholder:text-slate-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10'
            : 'border-slate-200 bg-white text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-100',
    ].join(' ')

    const buttonClass = [
        'inline-flex h-11 items-center justify-center gap-2 rounded-2xl border px-4 text-sm font-semibold transition',
        isDark
            ? 'border-slate-700 bg-slate-950 text-slate-200 hover:bg-slate-900'
            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
    ].join(' ')

    const activeBadgeClass = [
        'ml-0.5 inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[11px] font-bold',
        isDark
            ? 'bg-blue-500/15 text-blue-300'
            : 'bg-blue-50 text-blue-700',
    ].join(' ')

    return (
        <div className="mb-4 space-y-3">
            <div className="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div className="relative min-w-0 flex-1">
                    <Search
                        className={[
                            'pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2',
                            isDark ? 'text-slate-500' : 'text-slate-400',
                        ].join(' ')}
                    />

                    <input
                        value={search}
                        onChange={(event) => onSearchChange(event.target.value)}
                        placeholder={searchPlaceholder}
                        className={inputClass}
                    />
                </div>

                <div className="flex shrink-0 flex-wrap items-center gap-2">
                    <button
                        type="button"
                        onClick={onOpenFilters}
                        className={buttonClass}
                    >
                        <SlidersHorizontal className="h-4 w-4" />
                        Filters
                        {activeCount > 0 ? (
                            <span className={activeBadgeClass}>{activeCount}</span>
                        ) : null}
                    </button>

                    {hasActiveFilters ? (
                        <button
                            type="button"
                            onClick={onReset}
                            className={buttonClass}
                        >
                            <RotateCcw className="h-4 w-4" />
                            Reset
                        </button>
                    ) : null}
                </div>
            </div>

            {children ? <div>{children}</div> : null}
        </div>
    )
}