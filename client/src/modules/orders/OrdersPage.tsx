import { useCallback, useEffect, useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import { Eye } from 'lucide-react'
import { useOutletContext } from 'react-router-dom'
import ChartToggle from '../../shared/components/ChartToggle'
import SectionCard from '../../shared/components/SectionCard'
import StatCard from '../../shared/components/StatCard'
import RightDrawer from '../../shared/components/RightDrawer'
import SortableHeader from '../../shared/components/SortableHeader'
import DedicatedEntitySearch from '../../shared/components/DedicatedEntitySearch'
import FilterDrawer from '../../shared/components/FilterDrawer'
import FilterToolbar from '../../shared/components/FilterToolbar'
import AppliedFilterChips from '../../shared/components/AppliedFilterChips'
import { getOrderDetail, getOrdersOverview } from './orders.api'
import type { DashboardOutletContext } from '../../app/DashboardLayout'
import type { LookupResult } from '../../shared/types/lookup.types'
import type {
  OrderDetail,
  OrderType,
  OrdersResponse,
  PurchaseOrderDetailItem,
  RecentOrder,
  SalesOrderDetailItem,
} from './orders.types'
import { useMultiSort } from '../../shared/hooks/useMultiSort'
import { formatCompactNumber, formatCurrency, formatNumber, formatEnumLabel, formatDate, formatPhoneNumber } from '../../shared/utils/format'
import DatePickerInput from '../../shared/components/DatePickerInput'

const ORDERS_MAX_DATE = '2026-03-31'

const DEFAULT_DATE_RANGE = {
  startDate: '2026-03-01',
  endDate: '2026-03-31',
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
      className={`inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${getStatusClass(
        value,
        isDark,
      )}`}
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
              className={`px-2 text-sm ${isDark ? 'text-slate-500' : 'text-slate-400'}`}
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
  const { setHeaderRange, setHeaderDateRangeControl, theme } =
    useOutletContext<DashboardOutletContext>()

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
  const [filtersOpen, setFiltersOpen] = useState(false)

  const [selectedOrder, setSelectedOrder] = useState<OrderDetail | null>(null)
  const [detailLoading, setDetailLoading] = useState(false)
  const [detailError, setDetailError] = useState<string | null>(null)

  const { sortParam, cycleSort, getSort, getSortIndex } = useMultiSort(
    [{ field: 'date', direction: 'desc' }],
    { onChange: () => setPage(1) },
  )

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
    setFiltersOpen(false)
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
          sort: sortParam,
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
    sortParam,
  ])

  async function openOrderById(type: OrderType, id: number) {
    try {
      setDetailLoading(true)
      setDetailError(null)
      setSelectedOrder(null)

      const response = await getOrderDetail(type, id)
      setSelectedOrder(response)
    } catch {
      setDetailError('Failed to load order details.')
    } finally {
      setDetailLoading(false)
    }
  }

  function openOrderDetail(order: RecentOrder) {
    void openOrderById(order.type, order.id)
  }

  function openLookupOrder(result: LookupResult) {
    if (result.type === 'sales' || result.type === 'purchase') {
      void openOrderById(result.type, result.id)
    }
  }

  function resetFilters() {
    setSearch('')
    setStatuses([])
    setChannels([])
    setSupplier('all')
    setPerPage(10)
    updateDateRange(DEFAULT_DATE_RANGE)
    setPage(1)
  }

  const filterOptions = useMemo(
    () =>
      data?.filterOptions ?? {
        statuses: [],
        channels: [],
        suppliers: [],
      },
    [data],
  )

  const selectedSupplierName = useMemo(() => {
    if (supplier === 'all') return null

    return filterOptions.suppliers.find((item) => String(item.id) === String(supplier))?.name ?? null
  }, [filterOptions.suppliers, supplier])

  const dateRangeChanged =
    dateRange.startDate !== DEFAULT_DATE_RANGE.startDate ||
    dateRange.endDate !== DEFAULT_DATE_RANGE.endDate

  const activeFilterCount =
    statuses.length +
    (orderType === 'sales' ? channels.length : 0) +
    (orderType === 'purchase' && supplier !== 'all' ? 1 : 0) +
    (dateRangeChanged ? 1 : 0)

  const appliedFilterChips = useMemo(() => {
    const chips: string[] = []

    if (dateRangeChanged) {
      chips.push(`Date: ${formatDateRangeLabel(dateRange.startDate, dateRange.endDate)}`)
    }

    statuses.forEach((status) => {
      chips.push(`Status: ${formatEnumLabel(status)}`)
    })

    if (orderType === 'sales') {
      channels.forEach((channel) => {
        chips.push(`Channel: ${formatEnumLabel(channel)}`)
      })
    }

    if (orderType === 'purchase' && supplier !== 'all') {
      chips.push(`Supplier: ${selectedSupplierName ?? supplier}`)
    }

    return chips
  }, [
    channels,
    dateRange.endDate,
    dateRange.startDate,
    dateRangeChanged,
    orderType,
    selectedSupplierName,
    statuses,
    supplier,
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

  return (
    <div className="space-y-6">
      <div className="flex flex-col justify-between gap-4 xl:flex-row xl:items-end">
        <div>
          <h1 className={`text-3xl font-semibold tracking-tight ${isDark ? 'text-white' : 'text-slate-950'}`}>
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

      <DedicatedEntitySearch
        entity={orderType === 'sales' ? 'sales-orders' : 'purchase-orders'}
        placeholder={orderType === 'sales' ? 'Find SO across all dates...' : 'Find PO across all dates...'}
        onOpenResult={openLookupOrder}
        variant={theme}
      />

      <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
        <StatCard
          title="Total Orders"
          value={formatCompactNumber(summary?.totalOrders ?? 0)}
          helperText="Within selected date range"
          compact
          variant={theme}
        />
        <StatCard
          title="Open Orders"
          value={formatCompactNumber(summary?.openOrders ?? 0)}
          helperText="Pending or in progress"
          compact
          variant={theme}
        />
        <StatCard
          title={completedLabel}
          value={formatCompactNumber(summary?.completedOrders ?? 0)}
          helperText="Completed workflow"
          compact
          variant={theme}
        />
        <StatCard
          title={attentionLabel}
          value={formatCompactNumber(summary?.attentionOrders ?? 0)}
          helperText="Needs review"
          compact
          variant={theme}
        />
      </div>

      <SectionCard
        title={`${orderType === 'sales' ? 'Sales' : 'Purchase'} Orders`}
        description="Table search respects the selected date range. Direct lookup above ignores date filters."
        variant={theme}
      >
        <FilterToolbar
          search={search}
          onSearchChange={(value) => {
            setSearch(value)
            setPage(1)
          }}
          searchPlaceholder={orderType === 'sales' ? 'Search SO, customer, phone...' : 'Search PO, supplier, contact...'}
          activeCount={activeFilterCount}
          onOpenFilters={() => setFiltersOpen(true)}
          onReset={resetFilters}
          variant={theme}
        >
          <AppliedFilterChips chips={appliedFilterChips} variant={theme} />
        </FilterToolbar>

        <div className="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
              <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
                <tr>
                  <SortableHeader
                    label="Order"
                    field="orderNumber"
                    sort={getSort('orderNumber')}
                    sortIndex={getSortIndex('orderNumber')}
                    onSort={cycleSort}
                  />
                  <SortableHeader
                    label={counterpartyLabel}
                    field="counterpartyName"
                    sort={getSort('counterpartyName')}
                    sortIndex={getSortIndex('counterpartyName')}
                    onSort={cycleSort}
                  />
                  <SortableHeader
                    label="Date"
                    field="date"
                    sort={getSort('date')}
                    sortIndex={getSortIndex('date')}
                    onSort={cycleSort}
                  />
                  <SortableHeader
                    label="Status"
                    field="status"
                    sort={getSort('status')}
                    sortIndex={getSortIndex('status')}
                    onSort={cycleSort}
                  />
                  <SortableHeader
                    label={sourceLabel}
                    field="source"
                    sort={getSort('source')}
                    sortIndex={getSortIndex('source')}
                    onSort={cycleSort}
                  />
                  <SortableHeader
                    label="Items"
                    field="itemCount"
                    sort={getSort('itemCount')}
                    sortIndex={getSortIndex('itemCount')}
                    onSort={cycleSort}
                    align="right"
                  />
                  <SortableHeader
                    label="Total"
                    field="totalValue"
                    sort={getSort('totalValue')}
                    sortIndex={getSortIndex('totalValue')}
                    onSort={cycleSort}
                    align="right"
                  />
                  <th className="px-4 py-3" />
                </tr>
              </thead>

              <tbody className={`divide-y ${isDark ? 'divide-slate-800 bg-slate-900' : 'divide-slate-100 bg-white'}`}>
                {loading ? (
                  <tr>
                    <td colSpan={8} className={`px-4 py-10 text-center text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
                      Loading orders...
                    </td>
                  </tr>
                ) : error ? (
                  <tr>
                    <td colSpan={8} className="px-4 py-10 text-center text-sm text-rose-600">
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
                    <td colSpan={8} className={`px-4 py-10 text-center text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
                      No orders found for the selected filters.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>

        {pagination ? (
          <div className="mt-4 flex flex-col justify-between gap-3 md:flex-row md:items-center">
            <p className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
              Showing <span className="font-semibold">{pagination.from}</span> to{' '}
              <span className="font-semibold">{pagination.to}</span> of{' '}
              <span className="font-semibold">{pagination.total}</span> orders
            </p>

            <PaginationControls
              currentPage={pagination.currentPage}
              lastPage={pagination.lastPage}
              onPageChange={setPage}
              variant={theme}
            />
          </div>
        ) : null}
      </SectionCard>

      <FilterDrawer
        open={filtersOpen}
        title={`${orderType === 'sales' ? 'Sales' : 'Purchase'} Order Filters`}
        activeCount={activeFilterCount}
        onClose={() => setFiltersOpen(false)}
        onReset={resetFilters}
        onApply={() => setFiltersOpen(false)}
        variant={theme}
      >
        <div className="grid grid-cols-1 gap-4">
          <FilterLabel label="Start Date" variant={theme}>
            <DatePickerInput
              value={dateRange.startDate}
              maxDate={ORDERS_MAX_DATE}
              onChange={(value) => {
                updateDateRange({
                  startDate: value,
                  endDate: dateRange.endDate,
                })
              }}
              placeholder="Start date"
              variant={theme}
            />
          </FilterLabel>

          <FilterLabel label="End Date" variant={theme}>
            <DatePickerInput
              value={dateRange.endDate}
              minDate={dateRange.startDate}
              maxDate={ORDERS_MAX_DATE}
              onChange={(value) => {
                updateDateRange({
                  startDate: dateRange.startDate,
                  endDate: value,
                })
              }}
              placeholder="End date"
              variant={theme}
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
                  <option key={item.id} value={item.id}>
                    {item.name}
                  </option>
                ))}
              </select>
            </FilterLabel>
          ) : null}

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

          <FilterLabel label="Rows Per Page" variant={theme}>
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
        </div>
      </FilterDrawer>

      <OrderDetailDrawer
        order={selectedOrder}
        loading={detailLoading}
        error={detailError}
        open={detailLoading || Boolean(detailError) || Boolean(selectedOrder)}
        onClose={() => {
          setSelectedOrder(null)
          setDetailError(null)
        }}
        variant={theme}
      />
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
    <tr className={isDark ? 'hover:bg-slate-950/70' : 'hover:bg-slate-50'}>
      <td className="px-4 py-4">
        <p className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>{order.orderNumber}</p>
        <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
          {orderType === 'sales' ? 'Sales Order' : 'Purchase Order'}
        </p>
      </td>
      <td className="px-4 py-4">
        <p className={`font-medium ${isDark ? 'text-slate-200' : 'text-slate-800'}`}>{order.counterpartyName}</p>
        {order.counterpartyMeta ? (
          <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>{order.counterpartyMeta}</p>
        ) : null}
      </td>
      <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
        {formatDate(order.date)}
        {order.paymentStatus ? (
          <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
            Payment: {formatEnumLabel(order.paymentStatus)}
          </p>
        ) : null}
      </td>
      <td className="px-4 py-4">
        <StatusBadge value={order.status} variant={variant} />
      </td>
      <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
        {orderType === 'sales' ? formatEnumLabel(order.channel) : formatDate(order.expectedAt)}
      </td>
      <td className={`px-4 py-4 text-right text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
        <p>{formatNumber(order.itemCount)} lines</p>
        <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
          {formatNumber(order.totalQuantity)} units
        </p>
      </td>
      <td className={`px-4 py-4 text-right font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
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
          <Eye className="h-4 w-4" />
          View
        </button>
      </td>
    </tr>
  )
}

function OrderDetailDrawer({
  order,
  loading,
  error,
  open,
  onClose,
  variant,
}: {
  order: OrderDetail | null
  loading: boolean
  error: string | null
  open: boolean
  onClose: () => void
  variant: 'light' | 'dark'
}) {
  return (
    <RightDrawer
      open={open}
      title={order ? order.orderNumber : 'Order Details'}
      subtitle={
        order
          ? order.type === 'sales'
            ? 'Sales order details and line items'
            : 'Purchase order details and line items'
          : 'Loading order information'
      }
      onClose={onClose}
      widthClassName="max-w-5xl"
    >
      {loading ? (
        <div className="rounded-2xl bg-slate-50 p-5 text-sm text-slate-500">Loading order details...</div>
      ) : error ? (
        <div className="rounded-2xl bg-rose-50 p-5 text-sm text-rose-700">{error}</div>
      ) : order ? (
        <OrderDetailContent order={order} variant={variant} />
      ) : null}
    </RightDrawer>
  )
}

function DetailInfoCard({
  title,
  children,
  variant,
}: {
  title: string
  children: ReactNode
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <div className={`rounded-2xl border p-4 ${isDark ? 'border-slate-800 bg-slate-950' : 'border-slate-200 bg-slate-50'}`}>
      <p className={`text-xs font-semibold uppercase tracking-wide ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
        {title}
      </p>
      <div className={`mt-3 text-sm ${isDark ? 'text-slate-300' : 'text-slate-700'}`}>{children}</div>
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
    <div className="space-y-5">
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <DetailInfoCard title={order.type === 'sales' ? 'Customer' : 'Supplier'} variant={variant}>
          <p className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>{order.counterpartyName}</p>
          {order.counterpartyEmail ? <p>{order.counterpartyEmail}</p> : null}
          {order.counterpartyPhone ? <p>{formatPhoneNumber(order.counterpartyPhone)}</p> : null}
          {order.ownerName ? (
            <p>
              {order.type === 'sales' ? 'Sales: ' : 'Contact: '}
              {order.ownerName}
            </p>
          ) : null}
        </DetailInfoCard>

        <DetailInfoCard title="Order Status" variant={variant}>
          <div className="space-y-2">
            <StatusBadge value={order.status} variant={variant} />
            {order.paymentStatus ? <p>Payment: {formatEnumLabel(order.paymentStatus)}</p> : null}
            {order.channel ? <p>Channel: {formatEnumLabel(order.channel)}</p> : null}
          </div>
        </DetailInfoCard>

        <DetailInfoCard title="Dates" variant={variant}>
          <p>Ordered: {formatDate(order.orderedAt)}</p>
          {order.completedAt ? <p>Completed: {formatDate(order.completedAt)}</p> : null}
          {order.expectedAt ? <p>Expected: {formatDate(order.expectedAt)}</p> : null}
          {order.receivedAt ? <p>Received: {formatDate(order.receivedAt)}</p> : null}
        </DetailInfoCard>
      </div>

      {addressParts.length ? (
        <DetailInfoCard title="Address" variant={variant}>
          {addressParts.join(', ')}
        </DetailInfoCard>
      ) : null}

      <div className="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <TotalBox label="Subtotal" value={order.totals.subtotal} variant={variant} />
        <TotalBox label="Discount" value={order.totals.discountAmount} variant={variant} />
        <TotalBox label="Tax" value={order.totals.taxAmount} variant={variant} />
        <TotalBox
          label="Shipping / Other"
          value={Number(order.totals.shippingAmount) + Number(order.totals.otherAmount)}
          variant={variant}
        />
        <TotalBox label="Grand Total" value={order.totals.grandTotal} variant={variant} strong />
      </div>

      <div>
        <h3 className={`mb-3 text-base font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>Line Items</h3>
        {order.type === 'sales' ? (
          <SalesItemsTable items={order.items as SalesOrderDetailItem[]} variant={variant} />
        ) : (
          <PurchaseItemsTable items={order.items as PurchaseOrderDetailItem[]} variant={variant} />
        )}
      </div>

      {order.notes ? (
        <DetailInfoCard title="Notes" variant={variant}>
          {order.notes}
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
    <div className={`rounded-2xl border p-4 ${isDark ? 'border-slate-800 bg-slate-950' : 'border-slate-200 bg-slate-50'}`}>
      <p className={`text-xs font-semibold uppercase tracking-wide ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
        {label}
      </p>
      <p className={`mt-1 font-data ${strong ? 'text-xl font-semibold' : 'text-base font-semibold'} ${isDark ? 'text-white' : 'text-slate-950'}`}>
        {formatCurrency(value)}
      </p>
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
    <div className="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
          <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
            <tr>
              {['Product', 'Qty', 'Unit Price', 'Discount', 'Final Unit', 'Cost', 'Subtotal', 'Total', 'Profit', 'Commission'].map((heading) => (
                <th key={heading} className="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                  {heading}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className={`divide-y ${isDark ? 'divide-slate-800 bg-slate-900' : 'divide-slate-100 bg-white'}`}>
            {items.map((item) => (
              <tr key={item.id}>
                <td className="px-3 py-3">
                  <p className={`font-medium ${isDark ? 'text-white' : 'text-slate-950'}`}>{item.productTitle}</p>
                  <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
                    {[item.brandName, item.sku, item.modelNumber].filter(Boolean).join(' / ')}
                  </p>
                </td>
                <td className="px-3 py-3 text-sm">{formatNumber(item.quantity)}</td>
                <td className="px-3 py-3 text-sm">{formatCurrency(item.unitPrice)}</td>
                <td className="px-3 py-3 text-sm">{formatCurrency(item.discountAmount)}</td>
                <td className="px-3 py-3 text-sm">{formatCurrency(item.finalUnitPrice)}</td>
                <td className="px-3 py-3 text-sm">{formatCurrency(item.costBasis)}</td>
                <td className="px-3 py-3 text-sm">{formatCurrency(item.lineSubtotal)}</td>
                <td className="px-3 py-3 text-sm">{formatCurrency(item.lineTotal)}</td>
                <td className="px-3 py-3 text-sm">{formatCurrency(item.lineProfit)}</td>
                <td className="px-3 py-3 text-sm">{formatCurrency(item.commissionTotal)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
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
    <div className="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
          <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
            <tr>
              {['Product', 'Qty', 'Unit Cost', 'Line Total'].map((heading) => (
                <th key={heading} className="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                  {heading}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className={`divide-y ${isDark ? 'divide-slate-800 bg-slate-900' : 'divide-slate-100 bg-white'}`}>
            {items.map((item) => (
              <tr key={item.id}>
                <td className="px-3 py-3">
                  <p className={`font-medium ${isDark ? 'text-white' : 'text-slate-950'}`}>{item.productTitle}</p>
                  <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
                    {[item.brandName, item.sku, item.modelNumber].filter(Boolean).join(' / ')}
                  </p>
                </td>
                <td className="px-3 py-3 text-sm">{formatNumber(item.quantity)}</td>
                <td className="px-3 py-3 text-sm">{formatCurrency(item.unitCost)}</td>
                <td className="px-3 py-3 text-sm">{formatCurrency(item.lineTotal)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}