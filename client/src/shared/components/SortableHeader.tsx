import { ArrowDown, ArrowUp, ChevronsUpDown } from 'lucide-react'
import type { SortItem } from '../types/sort.types'

type SortableHeaderProps = {
  label: string
  field: string
  sort?: SortItem
  sortIndex?: number
  onSort: (field: string) => void
  align?: 'left' | 'right' | 'center'
  className?: string
}

const alignClass = {
  left: 'justify-start text-left',
  right: 'justify-end text-right',
  center: 'justify-center text-center',
}

export default function SortableHeader({
  label,
  field,
  sort,
  sortIndex,
  onSort,
  align = 'left',
  className = '',
}: SortableHeaderProps) {
  return (
    <th
      scope="col"
      className={`px-4 py-3 text-xs font-semibold uppercase tracking-wide ${className}`}
    >
      <button
        type="button"
        onClick={() => onSort(field)}
        className={`group inline-flex w-full items-center gap-1.5 rounded-md text-slate-500 transition hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-300 dark:text-slate-400 dark:hover:text-slate-100 dark:focus:ring-slate-700 ${alignClass[align]}`}
        title="Click to cycle: none → ascending → descending"
      >
        <span>{label}</span>

        {sort?.direction === 'asc' ? (
          <ArrowUp className="h-3.5 w-3.5" aria-hidden="true" />
        ) : sort?.direction === 'desc' ? (
          <ArrowDown className="h-3.5 w-3.5" aria-hidden="true" />
        ) : (
          <ChevronsUpDown
            className="h-3.5 w-3.5 opacity-40 group-hover:opacity-80"
            aria-hidden="true"
          />
        )}

        {sortIndex !== undefined ? (
          <span className="ml-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-slate-900 px-1 text-[10px] font-semibold text-white dark:bg-slate-100 dark:text-slate-950">
            {sortIndex + 1}
          </span>
        ) : null}
      </button>
    </th>
  )
}