import { Eye } from 'lucide-react'
import type { InventoryPagination, InventoryProductRow } from '../../types/inventory'
import type { SortItem } from '../../types/sort'
import SortableHeader from '../../components/common/SortableHeader'
import { formatCurrency, formatNumber } from '../../utils/format'

export const INVENTORY_MAX_DATE = '2026-03-31'

export const DEFAULT_INVENTORY_DATE_RANGE = {
  startDate: '2026-03-01',
  endDate: '2026-03-31',
}

export function formatEnumLabel(value: string | null | undefined) {
  if (!value) return '-'

  return value
    .split('_')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ')
}

export function formatDate(value: string | null | undefined) {
  if (!value) return '-'

  return new Date(`${value}T00:00:00`).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

export function formatDateRangeLabel(startDate: string, endDate: string) {
  const start = new Date(`${startDate}T00:00:00`)
  const end = new Date(`${endDate}T00:00:00`)
  const sameYear = start.getFullYear() === end.getFullYear()

  const startLabel = start.toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    ...(sameYear ? {} : { year: 'numeric' }),
  })

  const endLabel = end.toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  })

  return `${startLabel} - ${endLabel}`
}

export function normalizeDateRange(range: { startDate: string; endDate: string }) {
  let startDate = range.startDate || DEFAULT_INVENTORY_DATE_RANGE.startDate
  let endDate = range.endDate || DEFAULT_INVENTORY_DATE_RANGE.endDate

  if (startDate > INVENTORY_MAX_DATE) startDate = INVENTORY_MAX_DATE
  if (endDate > INVENTORY_MAX_DATE) endDate = INVENTORY_MAX_DATE
  if (startDate > endDate) endDate = startDate

  return { startDate, endDate }
}

export function InventoryStatusBadge({
  status,
  variant,
}: {
  status: string
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  const className =
    status === 'out_of_stock'
      ? isDark
        ? 'bg-rose-500/10 text-rose-300 ring-rose-500/20'
        : 'bg-rose-50 text-rose-700 ring-rose-200'
      : status === 'low_stock'
        ? isDark
          ? 'bg-amber-500/10 text-amber-300 ring-amber-500/20'
          : 'bg-amber-50 text-amber-700 ring-amber-200'
        : isDark
          ? 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/20'
          : 'bg-emerald-50 text-emerald-700 ring-emerald-200'

  return (
    <span
      className={`inline-flex whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold leading-none ring-1 ring-inset ${className}`}
    >
      {formatEnumLabel(status)}
    </span>
  )
}

export function FilterLabel({
  label,
  children,
  variant,
}: {
  label: string
  children: React.ReactNode
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <label className="flex flex-col gap-1">
      <span className={`text-xs font-semibold ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
        {label}
      </span>
      {children}
    </label>
  )
}

export function CheckboxGroup({
  label,
  options,
  selected,
  onChange,
  variant,
}: {
  label: string
  options: Array<{ label: string; value: string }>
  selected: string[]
  onChange: (value: string[]) => void
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  function toggleValue(value: string) {
    if (selected.includes(value)) {
      onChange(selected.filter((item) => item !== value))
      return
    }

    onChange([...selected, value])
  }

  if (!options.length) return null

  return (
    <div className="space-y-2">
      <p className={`text-xs font-semibold ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
        {label}
      </p>

      <div className="flex flex-wrap gap-2">
        {options.map((option) => {
          const active = selected.includes(option.value)

          return (
            <button
              key={option.value}
              type="button"
              onClick={() => toggleValue(option.value)}
              className={[
                'whitespace-nowrap rounded-full border px-3 py-1.5 text-xs font-semibold transition',
                active
                  ? isDark
                    ? 'border-blue-500 bg-blue-500/10 text-blue-300'
                    : 'border-blue-200 bg-blue-50 text-blue-700'
                  : isDark
                    ? 'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'
                    : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300',
              ].join(' ')}
            >
              {option.label}
            </button>
          )
        })}
      </div>
    </div>
  )
}

function getPaginationItems(currentPage: number, lastPage: number) {
  const pages: Array<number | 'ellipsis-left' | 'ellipsis-right'> = []

  if (lastPage <= 7) {
    for (let page = 1; page <= lastPage; page += 1) pages.push(page)
    return pages
  }

  pages.push(1)

  if (currentPage > 4) pages.push('ellipsis-left')

  const startPage = Math.max(2, currentPage - 1)
  const endPage = Math.min(lastPage - 1, currentPage + 1)

  for (let page = startPage; page <= endPage; page += 1) pages.push(page)

  if (currentPage < lastPage - 3) pages.push('ellipsis-right')

  pages.push(lastPage)

  return pages
}

export function PaginationControls({
  pagination,
  onPageChange,
  variant,
}: {
  pagination: InventoryPagination
  onPageChange: (page: number) => void
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  const buttonClass = [
    'inline-flex h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-sm font-semibold transition',
    isDark
      ? 'border-slate-700 bg-slate-950 text-slate-200 hover:bg-slate-900 disabled:text-slate-600'
      : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:text-slate-300',
  ].join(' ')

  const activeClass = [
    'inline-flex h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-sm font-semibold',
    isDark
      ? 'border-blue-500 bg-blue-500/10 text-blue-300'
      : 'border-blue-200 bg-blue-50 text-blue-700',
  ].join(' ')

  return (
    <div className="flex flex-col justify-between gap-3 md:flex-row md:items-center">
      <p className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
        Showing <span className="font-semibold">{pagination.from}</span> to{' '}
        <span className="font-semibold">{pagination.to}</span> of{' '}
        <span className="font-semibold">{pagination.total}</span> items
      </p>

      <div className="flex items-center gap-2">
        <button
          type="button"
          disabled={pagination.currentPage <= 1}
          onClick={() => onPageChange(Math.max(pagination.currentPage - 1, 1))}
          className={`${buttonClass} disabled:cursor-not-allowed disabled:opacity-50`}
        >
          Previous
        </button>

        {getPaginationItems(pagination.currentPage, pagination.lastPage).map((item) =>
          typeof item === 'string' ? (
            <span key={item} className={`px-2 text-sm ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
              ...
            </span>
          ) : (
            <button
              key={item}
              type="button"
              onClick={() => onPageChange(item)}
              className={item === pagination.currentPage ? activeClass : buttonClass}
            >
              {item}
            </button>
          ),
        )}

        <button
          type="button"
          disabled={pagination.currentPage >= pagination.lastPage}
          onClick={() => onPageChange(Math.min(pagination.currentPage + 1, pagination.lastPage))}
          className={`${buttonClass} disabled:cursor-not-allowed disabled:opacity-50`}
        >
          Next
        </button>
      </div>
    </div>
  )
}

export function ProductTable({
  rows,
  variant,
  onView,
  getSort,
  getSortIndex,
  onSort,
}: {
  rows: InventoryProductRow[]
  variant: 'light' | 'dark'
  onView: (row: InventoryProductRow) => void
  getSort: (field: string) => SortItem | undefined
  getSortIndex: (field: string) => number | undefined
  onSort: (field: string) => void
}) {
  const isDark = variant === 'dark'

  return (
    <div className="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
          <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
            <tr>
              <SortableHeader
                label="Product"
                field="product"
                sort={getSort('product')}
                sortIndex={getSortIndex('product')}
                onSort={onSort}
              />
              <SortableHeader
                label="Category"
                field="category"
                sort={getSort('category')}
                sortIndex={getSortIndex('category')}
                onSort={onSort}
              />
              <SortableHeader
                label="Brand"
                field="brand"
                sort={getSort('brand')}
                sortIndex={getSortIndex('brand')}
                onSort={onSort}
              />
              <SortableHeader
                label="Stock"
                field="currentStock"
                sort={getSort('currentStock')}
                sortIndex={getSortIndex('currentStock')}
                onSort={onSort}
                align="right"
              />
              <SortableHeader
                label="Avg Cost"
                field="avgUnitCost"
                sort={getSort('avgUnitCost')}
                sortIndex={getSortIndex('avgUnitCost')}
                onSort={onSort}
                align="right"
              />
              <SortableHeader
                label="Value"
                field="stockValue"
                sort={getSort('stockValue')}
                sortIndex={getSortIndex('stockValue')}
                onSort={onSort}
                align="right"
              />
              <SortableHeader
                label="Last Sold"
                field="lastSoldAt"
                sort={getSort('lastSoldAt')}
                sortIndex={getSortIndex('lastSoldAt')}
                onSort={onSort}
              />
              <SortableHeader
                label="Status"
                field="status"
                sort={getSort('status')}
                sortIndex={getSortIndex('status')}
                onSort={onSort}
              />
              <th className="px-4 py-3" />
            </tr>
          </thead>

          <tbody className={`divide-y ${isDark ? 'divide-slate-800 bg-slate-900' : 'divide-slate-100 bg-white'}`}>
            {rows.map((row) => (
              <tr key={row.id} className={isDark ? 'hover:bg-slate-950/70' : 'hover:bg-slate-50'}>
                <td className="whitespace-nowrap px-4 py-4">
                  <p className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>{row.title}</p>
                  <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
                    {[row.sku, row.modelNumber, row.isSerialized ? 'Serialized' : null].filter(Boolean).join(' / ')}
                  </p>
                </td>
                <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                  {[row.categoryName, row.subCategoryName].filter(Boolean).join(' / ') || '-'}
                </td>
                <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                  {row.brandName || '-'}
                </td>
                <td className={`px-4 py-4 text-right text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                  <p>{formatNumber(row.stockQty)}</p>
                  <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>units</p>
                </td>
                <td className={`px-4 py-4 text-right text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                  {formatCurrency(row.avgUnitCost)}
                </td>
                <td className={`px-4 py-4 text-right font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
                  {formatCurrency(row.inventoryValue)}
                </td>
                <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                  {formatDate(row.lastSoldAt)}
                </td>
                <td className="whitespace-nowrap px-4 py-4">
                  <InventoryStatusBadge status={row.status} variant={variant} />
                </td>
                <td className="px-4 py-4 text-right">
                  <button
                    type="button"
                    onClick={() => onView(row)}
                    className={[
                      'inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold transition',
                      isDark
                        ? 'border-slate-700 bg-slate-950 text-slate-200 hover:bg-slate-900'
                        : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
                    ].join(' ')}
                  >
                    <Eye className="h-4 w-4" />
                    View
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}