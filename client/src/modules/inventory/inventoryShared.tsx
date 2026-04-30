import type { ReactNode } from 'react'
import { Eye } from 'lucide-react'
import SortableHeader from '../../shared/components/SortableHeader'
import type { SortItem } from '../../shared/types/sort.types'
import type { InventoryPagination, InventoryProductRow } from './inventory.types'
import { formatCurrency, formatNumber, formatEnumLabel, formatDate } from '../../shared/utils/format'

export const DEFAULT_INVENTORY_DATE_RANGE = {
  startDate: '2026-03-01',
  endDate: '2026-03-31',
}

export function FilterLabel({
  label,
  children,
  variant,
}: {
  label: string
  children: ReactNode
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
    for (let page = 1; page <= lastPage; page += 1) {
      pages.push(page)
    }

    return pages
  }

  pages.push(1)

  if (currentPage > 4) {
    pages.push('ellipsis-left')
  }

  const startPage = Math.max(2, currentPage - 1)
  const endPage = Math.min(lastPage - 1, currentPage + 1)

  for (let page = startPage; page <= endPage; page += 1) {
    pages.push(page)
  }

  if (currentPage < lastPage - 3) {
    pages.push('ellipsis-right')
  }

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

  const baseButtonClass = [
    'inline-flex h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-sm font-semibold transition',
    isDark
      ? 'border-slate-700 bg-slate-950 text-slate-200 hover:bg-slate-900 disabled:text-slate-600'
      : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:text-slate-300',
  ].join(' ')

  const activeButtonClass = [
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
        <span className="font-semibold">{pagination.total}</span> products
      </p>

      <div className="flex flex-wrap items-center gap-2">
        <button
          type="button"
          disabled={pagination.currentPage <= 1}
          onClick={() => onPageChange(Math.max(pagination.currentPage - 1, 1))}
          className={`${baseButtonClass} disabled:cursor-not-allowed disabled:opacity-50`}
        >
          Previous
        </button>

        {getPaginationItems(pagination.currentPage, pagination.lastPage).map((item) => {
          if (typeof item === 'string') {
            return (
              <span
                key={item}
                className={`px-2 text-sm ${isDark ? 'text-slate-500' : 'text-slate-400'}`}
              >
                ...
              </span>
            )
          }

          return (
            <button
              key={item}
              type="button"
              onClick={() => onPageChange(item)}
              className={item === pagination.currentPage ? activeButtonClass : baseButtonClass}
            >
              {item}
            </button>
          )
        })}

        <button
          type="button"
          disabled={pagination.currentPage >= pagination.lastPage}
          onClick={() => onPageChange(Math.min(pagination.currentPage + 1, pagination.lastPage))}
          className={`${baseButtonClass} disabled:cursor-not-allowed disabled:opacity-50`}
        >
          Next
        </button>
      </div>
    </div>
  )
}

function ProductStatusBadge({
  status,
  variant,
}: {
  status: string
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'
  const normalized = status.toLowerCase()

  const className =
    normalized === 'out_of_stock'
      ? isDark
        ? 'bg-rose-500/10 text-rose-300 ring-rose-500/20'
        : 'bg-rose-50 text-rose-700 ring-rose-200'
      : normalized === 'low_stock'
        ? isDark
          ? 'bg-amber-500/10 text-amber-300 ring-amber-500/20'
          : 'bg-amber-50 text-amber-700 ring-amber-200'
        : normalized === 'stale_stock'
          ? isDark
            ? 'bg-violet-500/10 text-violet-300 ring-violet-500/20'
            : 'bg-violet-50 text-violet-700 ring-violet-200'
          : normalized === 'inactive'
            ? isDark
              ? 'bg-slate-800 text-slate-300 ring-slate-700'
              : 'bg-slate-100 text-slate-600 ring-slate-200'
            : isDark
              ? 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/20'
              : 'bg-emerald-50 text-emerald-700 ring-emerald-200'

  return (
    <span
      className={[
        'inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold leading-none ring-1 ring-inset',
        className,
      ].join(' ')}
    >
      {formatEnumLabel(status)}
    </span>
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
        <table className="w-full min-w-[1220px] divide-y divide-slate-200 dark:divide-slate-800">
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
                label="SKU"
                field="sku"
                sort={getSort('sku')}
                sortIndex={getSortIndex('sku')}
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
                label="Category"
                field="category"
                sort={getSort('category')}
                sortIndex={getSortIndex('category')}
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
                label="Value"
                field="inventoryValue"
                sort={getSort('inventoryValue')}
                sortIndex={getSortIndex('inventoryValue')}
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
                label="Last Received"
                field="lastReceivedAt"
                sort={getSort('lastReceivedAt')}
                sortIndex={getSortIndex('lastReceivedAt')}
                onSort={onSort}
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
                <td className="px-4 py-4">
                  <p className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
                    {row.title}
                  </p>
                  <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
                    {row.modelNumber || '-'}
                  </p>
                </td>

                <td className={`px-4 py-4 font-data text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                  {row.sku || '-'}
                </td>

                <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                  {row.brandName || '-'}
                </td>

                <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                  <p>{row.categoryName || '-'}</p>
                  {row.subCategoryName ? (
                    <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
                      {row.subCategoryName}
                    </p>
                  ) : null}
                </td>

                <td className={`px-4 py-4 text-right font-data text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                  {formatNumber(row.currentStock)}
                </td>

                <td className={`px-4 py-4 text-right font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
                  {formatCurrency(row.inventoryValue)}
                </td>

                <td className={`px-4 py-4 text-right font-data text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                  {formatCurrency(row.avgUnitCost)}
                </td>

                <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                  {formatDate(row.lastReceivedAt)}
                </td>

                <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                  {formatDate(row.lastSoldAt)}
                </td>

                <td className="px-4 py-4">
                  <ProductStatusBadge
                    status={row.alertType ?? row.status}
                    variant={variant}
                  />
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