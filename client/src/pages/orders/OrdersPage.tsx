import { useCallback, useEffect, useMemo, useState } from 'react'
import { Eye, X } from 'lucide-react'
import { useOutletContext } from 'react-router-dom'
import ChartToggle from '../../components/dashboard/ChartToggle'
import SectionCard from '../../components/dashboard/SectionCard'
import StatCard from '../../components/dashboard/StatCard'
import { getOrderDetail, getOrdersOverview } from '../../api/orders'
import type { DashboardOutletContext } from '../../layouts/DashboardLayout'
import type {
  OrderDetail,
  OrderType,
  OrdersResponse,
  PurchaseOrderDetailItem,
  RecentOrder,
  SalesOrderDetailItem,
} from '../../types/orders'
import {
  formatCompactNumber,
  formatCurrency,
  formatNumber,
} from '../../utils/format'

const ORDERS_MAX_DATE = '2026-03-31'

const DEFAULT_DATE_RANGE = {
  startDate: '2026-03-01',
  endDate: '2026-03-31',
}

function formatEnumLabel(value: string | null | undefined) {
  if (!value) return '-'

  return value
    .split('_')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ')
}

function formatDate(value: string | null | undefined) {
  if (!value) return '-'

  return new Date(`${value}T00:00:00`).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

function formatPhoneNumber(value: string | null | undefined) {
  if (!value) return null

  const trimmed = value.trim()
  const digits = trimmed.replace(/\D/g, '')

  if (digits.length === 11 && digits.startsWith('1')) {
    return `+1 (${digits.slice(1, 4)}) ${digits.slice(4, 7)}-${digits.slice(7)}`
  }

  if (digits.length === 10) {
    return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`
  }

  return trimmed
}

function formatDateRangeLabel(startDate: string, endDate: string) {
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

function clampDate(value: string, maxDate = ORDERS_MAX_DATE) {
  if (!value) return value
  return value > maxDate ? maxDate : value
}

function normalizeDateRange(range: { startDate: string; endDate: string }) {
  let startDate = clampDate(range.startDate || DEFAULT_DATE_RANGE.startDate)
  let endDate = clampDate(range.endDate || DEFAULT_DATE_RANGE.endDate)

  if (startDate > endDate) {
    endDate = startDate
  }

  return {
    startDate,
    endDate,
  }
}

function getStatusClass(status: string, isDark: boolean) {
  const normalized = status.toLowerCase()

  if (['delivered', 'fulfilled', 'paid'].includes(normalized)) {
    return isDark
      ? 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/20'
      : 'bg-emerald-50 text-emerald-700 ring-emerald-200'
  }

  if (['pending', 'draft', 'issued', 'confirmed', 'transit', 'shipped'].includes(normalized)) {
    return isDark
      ? 'bg-blue-500/10 text-blue-300 ring-blue-500/20'
      : 'bg-blue-50 text-blue-700 ring-blue-200'
  }

  if (['cancelled', 'returned', 'refunded'].includes(normalized)) {
    return isDark
      ? 'bg-rose-500/10 text-rose-300 ring-rose-500/20'
      : 'bg-rose-50 text-rose-700 ring-rose-200'
  }

  return isDark
    ? 'bg-slate-800 text-slate-300 ring-slate-700'
    : 'bg-slate-100 text-slate-600 ring-slate-200'
}

function StatusBadge({
  value,
  variant,
}: {
  value: string
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <span
      className={[
        'inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1',
        getStatusClass(value, isDark),
      ].join(' ')}
    >
      {formatEnumLabel(value)}
    </span>
  )
}

function FilterLabel({
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
    <label className="flex min-w-0 flex-col gap-1.5">
      <span
        className={[
          'text-xs font-semibold uppercase tracking-[0.1em]',
          isDark ? 'text-slate-500' : 'text-slate-400',
        ].join(' ')}
      >
        {label}
      </span>
      {children}
    </label>
  )
}

function CheckboxGroup({
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

  return (
    <div>
      <div
        className={[
          'mb-2 text-xs font-semibold uppercase tracking-[0.1em]',
          isDark ? 'text-slate-500' : 'text-slate-400',
        ].join(' ')}
      >
        {label}
      </div>

      <div className="flex flex-wrap gap-2">
        {options.map((option) => {
          const active = selected.includes(option.value)

          return (
            <button
              key={option.value}
              type="button"
              onClick={() => toggleValue(option.value)}
              className={[
                'rounded-full border px-3 py-1.5 text-xs font-semibold transition',
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

function PaginationControls({
  currentPage,
  lastPage,
  onPageChange,
  variant,
}: {
  currentPage: number
  lastPage: number
  onPageChange: (page: number) => void
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'
  const pageItems = getPaginationItems(currentPage, lastPage)

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
    <div className="flex flex-wrap items-center gap-2">
      <button
        type="button"
        disabled={currentPage <= 1}
        onClick={() => onPageChange(Math.max(currentPage - 1, 1))}
        className={`${baseButtonClass} disabled:cursor-not-allowed disabled:opacity-50`}
      >
        Previous
      </button>

      {pageItems.map((item) => {
        if (typeof item === 'string') {
          return (
            <span
              key={item}
              className={[
                'inline-flex h-10 min-w-10 items-center justify-center text-sm font-semibold',
                isDark ? 'text-slate-600' : 'text-slate-400',
              ].join(' ')}
            >
              ...
            </span>
          )
        }

        const active = item === currentPage

        return (
          <button
            key={item}
            type="button"
            onClick={() => onPageChange(item)}
            className={active ? activeButtonClass : baseButtonClass}
          >
            {item}
          </button>
        )
      })}

      <button
        type="button"
        disabled={currentPage >= lastPage}
        onClick={() => onPageChange(Math.min(currentPage + 1, lastPage))}
        className={`${baseButtonClass} disabled:cursor-not-allowed disabled:opacity-50`}
      >
        Next
      </button>
    </div>
  )
}

export default function OrdersPage() {
  const {
    setHeaderRange,
    setHeaderDateRangeControl,
    theme,
  } = useOutletContext<DashboardOutletContext>()

  const [orderType, setOrderType] = useState<OrderType>('sales')
  const [dateRange, setDateRange] = useState(DEFAULT_DATE_RANGE)

  const [search, setSearch] = useState('')
  const [statuses, setStatuses] = useState<string[]>([])
  const [channels, setChannels] = useState<string[]>([])
  const [supplier, setSupplier] = useState('all')

  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(10)

  const [data, setData] = useState<OrdersResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const [selectedOrder, setSelectedOrder] = useState<OrderDetail | null>(null)
  const [detailLoading, setDetailLoading] = useState(false)
  const [detailError, setDetailError] = useState<string | null>(null)

  const isDark = theme === 'dark'

  const updateDateRange = useCallback((nextRange: { startDate: string; endDate: string }) => {
    setDateRange(normalizeDateRange(nextRange))
    setPage(1)
  }, [])

  useEffect(() => {
    setHeaderRange({
      startDate: dateRange.startDate,
      endDate: dateRange.endDate,
      label: formatDateRangeLabel(dateRange.startDate, dateRange.endDate),
    })

    setHeaderDateRangeControl({
      enabled: true,
      startDate: dateRange.startDate,
      endDate: dateRange.endDate,
      maxDate: ORDERS_MAX_DATE,
      onChange: updateDateRange,
    })

    return () => {
      setHeaderRange(null)
      setHeaderDateRangeControl(null)
    }
  }, [
    dateRange.startDate,
    dateRange.endDate,
    setHeaderRange,
    setHeaderDateRangeControl,
    updateDateRange,
  ])

  useEffect(() => {
    setStatuses([])
    setChannels([])
    setSupplier('all')
    setSearch('')
    setPage(1)
    setSelectedOrder(null)
  }, [orderType])

  useEffect(() => {
    let active = true

    async function loadOrders() {
      try {
        setLoading(true)
        setError(null)

        const response = await getOrdersOverview({
          type: orderType,
          startDate: dateRange.startDate,
          endDate: dateRange.endDate,
          search,
          statuses,
          channels,
          suppliers: supplier === 'all' ? [] : [supplier],
          page,
          perPage,
        })

        if (!active) return

        setData(response)
      } catch {
        if (!active) return
        setError('Failed to load orders.')
      } finally {
        if (active) {
          setLoading(false)
        }
      }
    }

    loadOrders()

    return () => {
      active = false
    }
  }, [
    orderType,
    dateRange.startDate,
    dateRange.endDate,
    search,
    statuses,
    channels,
    supplier,
    page,
    perPage,
  ])

  const summary = data?.summary
  const pagination = data?.pagination

  const attentionLabel = orderType === 'sales' ? 'Exceptions' : 'Overdue'
  const completedLabel = orderType === 'sales' ? 'Delivered' : 'Fulfilled'
  const counterpartyLabel = orderType === 'sales' ? 'Customer' : 'Supplier'
  const sourceLabel = orderType === 'sales' ? 'Channel' : 'Expected'

  const inputClass = [
    'h-10 rounded-xl border px-3 text-sm outline-none transition',
    isDark
      ? 'border-slate-800 bg-slate-950 text-slate-200 placeholder:text-slate-600 focus:border-blue-500'
      : 'border-slate-200 bg-white text-slate-700 placeholder:text-slate-400 focus:border-blue-500',
  ].join(' ')

  function resetFilters() {
    setSearch('')
    setStatuses([])
    setChannels([])
    setSupplier('all')
    updateDateRange(DEFAULT_DATE_RANGE)
    setPage(1)
  }

  async function openOrderDetail(order: RecentOrder) {
    try {
      setDetailLoading(true)
      setDetailError(null)
      setSelectedOrder(null)

      const response = await getOrderDetail(order.type, order.id)
      setSelectedOrder(response)
    } catch {
      setDetailError('Failed to load order details.')
    } finally {
      setDetailLoading(false)
    }
  }

  const filterOptions = useMemo(
    () =>
      data?.filterOptions ?? {
        statuses: [],
        channels: [],
        suppliers: [],
      },
    [data]
  )

  return (
    <div className="space-y-8">
      <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 className={`text-xl font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
            Orders
          </h1>
          <p className={`mt-1 text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
            Track sales and purchase order activity for the selected date range.
          </p>
        </div>

        <ChartToggle
          value={orderType}
          onChange={(value) => setOrderType(value)}
          options={[
            { label: 'Sales Orders', value: 'sales' },
            { label: 'Purchase Orders', value: 'purchase' },
          ]}
          variant={theme}
        />
      </div>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <StatCard
          title="Total Orders"
          value={summary ? formatNumber(summary.totalOrders) : '-'}
          helperText="Selected period"
          variant={theme}
          compact
        />

        <StatCard
          title="Open Orders"
          value={summary ? formatNumber(summary.openOrders) : '-'}
          helperText={orderType === 'sales' ? 'Pending, confirmed, shipped' : 'Draft, issued, transit'}
          variant={theme}
          compact
        />

        <StatCard
          title={completedLabel}
          value={summary ? formatNumber(summary.completedOrders) : '-'}
          helperText={orderType === 'sales' ? 'Delivered orders' : 'Fulfilled orders'}
          variant={theme}
          compact
        />

        <StatCard
          title="Total Value"
          value={summary ? formatCompactNumber(summary.totalValue) : '-'}
          secondaryValue={summary ? formatCurrency(summary.totalValue) : undefined}
          variant={theme}
          compact
          monoSecondary
        />

        <StatCard
          title={attentionLabel}
          value={summary ? formatNumber(summary.attentionOrders) : '-'}
          helperText={orderType === 'sales' ? 'Cancelled, returned, refunded' : 'Past expected date'}
          variant={theme}
          compact
        />
      </div>

      <SectionCard
        title={orderType === 'sales' ? 'Recent Sales Orders' : 'Recent Purchase Orders'}
        description="Use filters to narrow the order list. The date range is also synced with the app header."
        variant={theme}
      >
        <div className="space-y-5">
          <div className="grid gap-4">
            <div
              className={[
                'grid gap-3',
                orderType === 'purchase'
                  ? 'xl:grid-cols-[minmax(220px,1fr)_220px_160px_160px_160px_auto]'
                  : 'xl:grid-cols-[minmax(220px,1fr)_160px_160px_160px_auto]',
              ].join(' ')}
            >
              <FilterLabel label="Search" variant={theme}>
                <input
                  type="search"
                  value={search}
                  onChange={(event) => {
                    setSearch(event.target.value)
                    setPage(1)
                  }}
                  placeholder={orderType === 'sales' ? 'Order, customer, phone...' : 'PO, supplier, contact...'}
                  className={inputClass}
                />
              </FilterLabel>

              {orderType === 'purchase' ? (
                <FilterLabel label="Supplier" variant={theme}>
                  <select
                    value={supplier}
                    onChange={(event) => {
                      setSupplier(event.target.value)
                      setPage(1)
                    }}
                    className={inputClass}
                  >
                    <option value="all">All suppliers</option>
                    {filterOptions.suppliers.map((item) => (
                      <option key={item.id} value={String(item.id)}>
                        {item.name}
                      </option>
                    ))}
                  </select>
                </FilterLabel>
              ) : null}

              <FilterLabel label="Start Date" variant={theme}>
                <input
                  type="date"
                  value={dateRange.startDate}
                  max={ORDERS_MAX_DATE}
                  onChange={(event) =>
                    updateDateRange({
                      startDate: event.target.value,
                      endDate: dateRange.endDate,
                    })
                  }
                  className={inputClass}
                />
              </FilterLabel>

              <FilterLabel label="End Date" variant={theme}>
                <input
                  type="date"
                  value={dateRange.endDate}
                  min={dateRange.startDate}
                  max={ORDERS_MAX_DATE}
                  onChange={(event) =>
                    updateDateRange({
                      startDate: dateRange.startDate,
                      endDate: event.target.value,
                    })
                  }
                  className={inputClass}
                />
              </FilterLabel>

              <FilterLabel label="Rows" variant={theme}>
                <select
                  value={perPage}
                  onChange={(event) => {
                    setPerPage(Number(event.target.value))
                    setPage(1)
                  }}
                  className={inputClass}
                >
                  <option value={10}>10 rows</option>
                  <option value={15}>15 rows</option>
                  <option value={25}>25 rows</option>
                  <option value={50}>50 rows</option>
                </select>
              </FilterLabel>

              <div className="flex items-end">
                <button
                  type="button"
                  onClick={resetFilters}
                  className={[
                    'h-10 rounded-xl border px-4 text-sm font-semibold transition',
                    isDark
                      ? 'border-slate-700 bg-slate-950 text-slate-200 hover:bg-slate-900'
                      : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
                  ].join(' ')}
                >
                  Reset
                </button>
              </div>
            </div>

            <div className="grid gap-4 xl:grid-cols-2">
              <CheckboxGroup
                label="Statuses"
                options={filterOptions.statuses.map((item) => ({
                  label: formatEnumLabel(item),
                  value: item,
                }))}
                selected={statuses}
                onChange={(value) => {
                  setStatuses(value)
                  setPage(1)
                }}
                variant={theme}
              />

              {orderType === 'sales' ? (
                <CheckboxGroup
                  label="Channels"
                  options={filterOptions.channels.map((item) => ({
                    label: formatEnumLabel(item),
                    value: item,
                  }))}
                  selected={channels}
                  onChange={(value) => {
                    setChannels(value)
                    setPage(1)
                  }}
                  variant={theme}
                />
              ) : null}
            </div>
          </div>

          <div
            className={[
              'overflow-hidden rounded-2xl border',
              isDark ? 'border-slate-800' : 'border-slate-200',
            ].join(' ')}
          >
            <div className="overflow-x-auto">
              <table className="min-w-[1080px] w-full border-collapse">
                <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
                  <tr>
                    {[
                      'Order',
                      counterpartyLabel,
                      'Date',
                      'Status',
                      sourceLabel,
                      'Items',
                      'Total',
                      '',
                    ].map((heading) => (
                      <th
                        key={heading || 'actions'}
                        className={[
                          'px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.1em]',
                          heading === 'Total' ? 'text-right' : '',
                          isDark ? 'text-slate-500' : 'text-slate-400',
                        ].join(' ')}
                      >
                        {heading}
                      </th>
                    ))}
                  </tr>
                </thead>

                <tbody
                  className={[
                    'divide-y',
                    isDark ? 'divide-slate-800 bg-slate-900' : 'divide-slate-100 bg-white',
                  ].join(' ')}
                >
                  {loading ? (
                    <tr>
                      <td
                        colSpan={8}
                        className={`px-4 py-12 text-center text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}
                      >
                        Loading orders...
                      </td>
                    </tr>
                  ) : error ? (
                    <tr>
                      <td
                        colSpan={8}
                        className={`px-4 py-12 text-center text-sm ${isDark ? 'text-rose-300' : 'text-rose-600'}`}
                      >
                        {error}
                      </td>
                    </tr>
                  ) : data?.recentOrders.length ? (
                    data.recentOrders.map((order) => (
                      <OrderTableRow
                        key={`${order.type}-${order.id}`}
                        order={order}
                        orderType={orderType}
                        variant={theme}
                        onView={() => openOrderDetail(order)}
                      />
                    ))
                  ) : (
                    <tr>
                      <td
                        colSpan={8}
                        className={`px-4 py-12 text-center text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}
                      >
                        No orders found for the selected filters.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>

          {pagination ? (
            <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
                Showing{' '}
                <span className="font-data font-semibold">
                  {pagination.from}
                </span>{' '}
                to{' '}
                <span className="font-data font-semibold">
                  {pagination.to}
                </span>{' '}
                of{' '}
                <span className="font-data font-semibold">
                  {pagination.total}
                </span>{' '}
                orders
              </div>

              <PaginationControls
                currentPage={pagination.currentPage}
                lastPage={pagination.lastPage}
                onPageChange={setPage}
                variant={theme}
              />
            </div>
          ) : null}
        </div>
      </SectionCard>

      {detailLoading || detailError || selectedOrder ? (
        <OrderDetailModal
          order={selectedOrder}
          loading={detailLoading}
          error={detailError}
          onClose={() => {
            setSelectedOrder(null)
            setDetailError(null)
          }}
          variant={theme}
        />
      ) : null}
    </div>
  )
}

function OrderTableRow({
  order,
  orderType,
  variant,
  onView,
}: {
  order: RecentOrder
  orderType: OrderType
  variant: 'light' | 'dark'
  onView: () => void
}) {
  const isDark = variant === 'dark'

  return (
    <tr className={isDark ? 'hover:bg-slate-800/60' : 'hover:bg-slate-50'}>
      <td className="px-4 py-4">
        <div className={`font-data text-sm font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
          {order.orderNumber}
        </div>
        <div className={`mt-1 text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
          {orderType === 'sales' ? 'Sales Order' : 'Purchase Order'}
        </div>
      </td>

      <td className="px-4 py-4">
        <div className={`max-w-[260px] truncate text-sm font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
          {order.counterpartyName}
        </div>
        {order.counterpartyMeta ? (
          <div className={`mt-1 text-xs ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
            {order.counterpartyMeta}
          </div>
        ) : null}
      </td>

      <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
        {formatDate(order.date)}
      </td>

      <td className="px-4 py-4">
        <StatusBadge value={order.status} variant={variant} />

        {order.paymentStatus ? (
          <div className={`mt-2 text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
            Payment: {formatEnumLabel(order.paymentStatus)}
          </div>
        ) : null}
      </td>

      <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
        {orderType === 'sales'
          ? formatEnumLabel(order.channel)
          : formatDate(order.expectedAt)}
      </td>

      <td className="px-4 py-4">
        <div className={`text-sm font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
          {formatNumber(order.itemCount)} lines
        </div>
        <div className={`mt-1 text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
          {formatNumber(order.totalQuantity)} units
        </div>
      </td>

      <td className={`px-4 py-4 text-right font-data text-sm font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
        {formatCurrency(order.totalValue)}
      </td>

      <td className="px-4 py-4 text-right">
        <button
          type="button"
          onClick={onView}
          className={[
            'inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold transition',
            isDark
              ? 'border-slate-700 bg-slate-950 text-slate-200 hover:bg-slate-900'
              : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
          ].join(' ')}
        >
          <Eye size={15} />
          View
        </button>
      </td>
    </tr>
  )
}

function OrderDetailModal({
  order,
  loading,
  error,
  onClose,
  variant,
}: {
  order: OrderDetail | null
  loading: boolean
  error: string | null
  onClose: () => void
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <div className="fixed inset-0 z-50 flex items-stretch justify-end bg-slate-950/40 backdrop-blur-sm">
      <div
        className={[
          'h-full w-full max-w-[980px] overflow-y-auto border-l shadow-2xl',
          isDark
            ? 'border-slate-800 bg-slate-950 text-slate-100'
            : 'border-slate-200 bg-white text-slate-900',
        ].join(' ')}
      >
        <div
          className={[
            'sticky top-0 z-10 flex items-start justify-between gap-4 border-b px-6 py-5',
            isDark ? 'border-slate-800 bg-slate-950' : 'border-slate-200 bg-white',
          ].join(' ')}
        >
          <div>
            <h2 className="text-lg font-semibold">
              {order ? order.orderNumber : 'Order Details'}
            </h2>
            <p className={`mt-1 text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
              {order
                ? order.type === 'sales'
                  ? 'Sales order details and line items'
                  : 'Purchase order details and line items'
                : 'Loading order information'}
            </p>
          </div>

          <button
            type="button"
            onClick={onClose}
            className={[
              'rounded-full border p-2 transition',
              isDark
                ? 'border-slate-800 bg-slate-900 text-slate-300 hover:bg-slate-800'
                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
            ].join(' ')}
          >
            <X size={18} />
          </button>
        </div>

        <div className="p-6">
          {loading ? (
            <div className={`py-20 text-center text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
              Loading order details...
            </div>
          ) : error ? (
            <div className={`py-20 text-center text-sm ${isDark ? 'text-rose-300' : 'text-rose-600'}`}>
              {error}
            </div>
          ) : order ? (
            <OrderDetailContent order={order} variant={variant} />
          ) : null}
        </div>
      </div>
    </div>
  )
}

function DetailInfoCard({
  title,
  children,
  variant,
}: {
  title: string
  children: React.ReactNode
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <div
      className={[
        'rounded-2xl border p-4',
        isDark ? 'border-slate-800 bg-slate-900' : 'border-slate-200 bg-slate-50',
      ].join(' ')}
    >
      <div className={`mb-3 text-xs font-semibold uppercase tracking-[0.12em] ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
        {title}
      </div>
      {children}
    </div>
  )
}

function OrderDetailContent({
  order,
  variant,
}: {
  order: OrderDetail
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'
  const addressParts = [
    order.address?.line1,
    order.address?.line2,
    order.address?.city,
    order.address?.state,
    order.address?.postalCode,
    order.address?.country,
  ].filter(Boolean)

  return (
    <div className="space-y-6">
      <div className="grid gap-4 xl:grid-cols-3">
        <DetailInfoCard title={order.type === 'sales' ? 'Customer' : 'Supplier'} variant={variant}>
          <div className="space-y-1">
            <div className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
              {order.counterpartyName}
            </div>
            {order.counterpartyEmail ? (
              <div className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
                {order.counterpartyEmail}
              </div>
            ) : null}
            {order.counterpartyPhone ? (
              <div className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
                {formatPhoneNumber(order.counterpartyPhone)}
              </div>
            ) : null}
            {order.ownerName ? (
              <div className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
                {order.type === 'sales' ? 'Sales: ' : 'Contact: '}
                {order.ownerName}
              </div>
            ) : null}
          </div>
        </DetailInfoCard>

        <DetailInfoCard title="Status" variant={variant}>
          <div className="space-y-3">
            <StatusBadge value={order.status} variant={variant} />
            {order.paymentStatus ? (
              <div className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
                Payment: {formatEnumLabel(order.paymentStatus)}
              </div>
            ) : null}
            {order.channel ? (
              <div className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
                Channel: {formatEnumLabel(order.channel)}
              </div>
            ) : null}
          </div>
        </DetailInfoCard>

        <DetailInfoCard title="Dates" variant={variant}>
          <div className={`space-y-1 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
            <div>Ordered: {formatDate(order.orderedAt)}</div>
            {order.completedAt ? <div>Completed: {formatDate(order.completedAt)}</div> : null}
            {order.expectedAt ? <div>Expected: {formatDate(order.expectedAt)}</div> : null}
            {order.receivedAt ? <div>Received: {formatDate(order.receivedAt)}</div> : null}
          </div>
        </DetailInfoCard>
      </div>

      {addressParts.length ? (
        <DetailInfoCard title={order.type === 'sales' ? 'Delivery Address' : 'Supplier Address'} variant={variant}>
          <div className={`text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
            {addressParts.join(', ')}
          </div>
        </DetailInfoCard>
      ) : null}

      <div className="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
        <TotalBox label="Subtotal" value={order.totals.subtotal} variant={variant} />
        <TotalBox label="Discount" value={order.totals.discountAmount} variant={variant} />
        <TotalBox label="Tax" value={order.totals.taxAmount} variant={variant} />
        <TotalBox label="Shipping" value={order.totals.shippingAmount} variant={variant} />
        <TotalBox label="Other" value={order.totals.otherAmount} variant={variant} />
        <TotalBox label="Grand Total" value={order.totals.grandTotal} variant={variant} strong />
      </div>

      <div>
        <h3 className={`mb-3 text-sm font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
          Line Items
        </h3>

        <div
          className={[
            'overflow-hidden rounded-2xl border',
            isDark ? 'border-slate-800' : 'border-slate-200',
          ].join(' ')}
        >
          <div className="overflow-x-auto">
            {order.type === 'sales' ? (
              <SalesItemsTable
                items={order.items as SalesOrderDetailItem[]}
                variant={variant}
              />
            ) : (
              <PurchaseItemsTable
                items={order.items as PurchaseOrderDetailItem[]}
                variant={variant}
              />
            )}
          </div>
        </div>
      </div>

      {order.notes ? (
        <DetailInfoCard title="Notes" variant={variant}>
          <div className={`text-sm leading-6 ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
            {order.notes}
          </div>
        </DetailInfoCard>
      ) : null}
    </div>
  )
}

function TotalBox({
  label,
  value,
  variant,
  strong = false,
}: {
  label: string
  value: number | string
  variant: 'light' | 'dark'
  strong?: boolean
}) {
  const isDark = variant === 'dark'

  return (
    <div
      className={[
        'rounded-2xl border p-4',
        isDark ? 'border-slate-800 bg-slate-900' : 'border-slate-200 bg-slate-50',
      ].join(' ')}
    >
      <div className={`text-xs font-semibold uppercase tracking-[0.12em] ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
        {label}
      </div>
      <div
        className={[
          'mt-2 font-data',
          strong ? 'text-base font-bold' : 'text-sm font-semibold',
          isDark ? 'text-white' : 'text-slate-950',
        ].join(' ')}
      >
        {formatCurrency(value)}
      </div>
    </div>
  )
}

function SalesItemsTable({
  items,
  variant,
}: {
  items: SalesOrderDetailItem[]
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <table className="min-w-[1320px] w-full border-collapse">
      <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
        <tr>
          {[
            'Product',
            'Qty',
            'Unit Price',
            'Discount',
            'Final Unit',
            'Cost',
            'Subtotal',
            'Total',
            'Profit',
            'Commission',
          ].map((heading) => (
            <th
              key={heading}
              className={[
                'px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.1em]',
                isDark ? 'text-slate-500' : 'text-slate-400',
              ].join(' ')}
            >
              {heading}
            </th>
          ))}
        </tr>
      </thead>

      <tbody className={['divide-y', isDark ? 'divide-slate-800 bg-slate-900' : 'divide-slate-100 bg-white'].join(' ')}>
        {items.map((item) => (
          <tr key={item.id}>
            <td className="px-4 py-4">
              <div className={`max-w-[280px] truncate text-sm font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
                {item.productTitle}
              </div>
              <div className={`mt-1 text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
                {[item.brandName, item.sku, item.modelNumber].filter(Boolean).join(' / ')}
              </div>
            </td>
            <td className="px-4 py-4 font-data text-sm">{formatNumber(item.quantity)}</td>
            <td className="px-4 py-4 font-data text-sm">{formatCurrency(item.unitPrice)}</td>
            <td className="px-4 py-4 font-data text-sm">{formatCurrency(item.discountAmount)}</td>
            <td className="px-4 py-4 font-data text-sm">{formatCurrency(item.finalUnitPrice)}</td>
            <td className="px-4 py-4 font-data text-sm">{formatCurrency(item.costBasis)}</td>
            <td className="px-4 py-4 font-data text-sm">{formatCurrency(item.lineSubtotal)}</td>
            <td className="px-4 py-4 font-data text-sm font-semibold">{formatCurrency(item.lineTotal)}</td>
            <td className="px-4 py-4 font-data text-sm">{formatCurrency(item.lineProfit)}</td>
            <td className="px-4 py-4 font-data text-sm">{formatCurrency(item.commissionTotal)}</td>
          </tr>
        ))}
      </tbody>
    </table>
  )
}

function PurchaseItemsTable({
  items,
  variant,
}: {
  items: PurchaseOrderDetailItem[]
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <table className="min-w-[820px] w-full border-collapse">
      <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
        <tr>
          {[
            'Product',
            'Qty',
            'Unit Cost',
            'Line Total',
          ].map((heading) => (
            <th
              key={heading}
              className={[
                'px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.1em]',
                isDark ? 'text-slate-500' : 'text-slate-400',
              ].join(' ')}
            >
              {heading}
            </th>
          ))}
        </tr>
      </thead>

      <tbody className={['divide-y', isDark ? 'divide-slate-800 bg-slate-900' : 'divide-slate-100 bg-white'].join(' ')}>
        {items.map((item) => (
          <tr key={item.id}>
            <td className="px-4 py-4">
              <div className={`max-w-[360px] truncate text-sm font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
                {item.productTitle}
              </div>
              <div className={`mt-1 text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
                {[item.brandName, item.sku, item.modelNumber].filter(Boolean).join(' / ')}
              </div>
            </td>
            <td className="px-4 py-4 font-data text-sm">{formatNumber(item.quantity)}</td>
            <td className="px-4 py-4 font-data text-sm">{formatCurrency(item.unitCost)}</td>
            <td className="px-4 py-4 font-data text-sm font-semibold">{formatCurrency(item.lineTotal)}</td>
          </tr>
        ))}
      </tbody>
    </table>
  )
}