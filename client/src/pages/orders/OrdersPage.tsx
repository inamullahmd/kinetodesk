import { useEffect, useMemo, useState } from 'react'
import { useOutletContext } from 'react-router-dom'
import {
  ArrowDown,
  ArrowUp,
  ArrowUpDown,
  Filter,
  Search,
  X,
} from 'lucide-react'
import DonutChart from '../../components/charts/DonutChart'
import OrdersTrendChart from '../../components/orders/OrdersTrendChart'
import SectionCard from '../../components/dashboard/SectionCard'
import StatCard from '../../components/dashboard/StatCard'
import { getOrders, getOrdersStats } from '../../api/orders'
import type {
  OrdersFilters,
  OrdersListResponse,
  OrdersStatsResponse,
} from '../../types/orders'
import type { DashboardOutletContext } from '../../layouts/DashboardLayout'
import {
  formatCompactNumber,
  formatCurrency,
  formatLabel,
  formatNumber,
} from '../../utils/format'

const STATUS_OPTIONS = [
  'pending',
  'processing',
  'shipped',
  'delivered',
  'cancelled',
  'returned',
  'refunded',
]

const CHANNEL_OPTIONS = ['online', 'in_store', 'phone']

type SortKey =
  | 'order_number'
  | 'ordered_at'
  | 'customer_name'
  | 'channel'
  | 'status'
  | 'grand_total'
  | 'employee_name'

type SortDirection = 'asc' | 'desc'

type SortItem = {
  key: SortKey
  direction: SortDirection
}

function statusClasses(status: string) {
  switch (status) {
    case 'pending':
      return 'bg-amber-50 text-amber-700 ring-amber-200'
    case 'processing':
      return 'bg-sky-50 text-sky-700 ring-sky-200'
    case 'shipped':
      return 'bg-indigo-50 text-indigo-700 ring-indigo-200'
    case 'delivered':
      return 'bg-emerald-50 text-emerald-700 ring-emerald-200'
    case 'cancelled':
      return 'bg-rose-50 text-rose-700 ring-rose-200'
    case 'returned':
    case 'refunded':
      return 'bg-orange-50 text-orange-700 ring-orange-200'
    default:
      return 'bg-slate-100 text-slate-700 ring-slate-200'
  }
}

function parseSortString(sort?: string): SortItem[] {
  if (!sort || !sort.trim()) {
    return []
  }

  return sort
    .split(',')
    .map((entry) => entry.trim())
    .filter((entry): entry is string => entry.length > 0)
    .map((entry): SortItem => {
      const direction: SortDirection = entry.startsWith('-') ? 'desc' : 'asc'

      return {
        key: entry.replace(/^-/, '') as SortKey,
        direction,
      }
    })
    .slice(0, 2)
}

function stringifySorts(sorts: SortItem[]) {
  if (!sorts.length) {
    return ''
  }

  return sorts
    .map((item) => (item.direction === 'desc' ? `-${item.key}` : item.key))
    .join(',')
}

function SortIcon({
  direction,
}: {
  direction: SortDirection | null
}) {
  if (direction === 'asc') {
    return <ArrowUp size={14} className="text-[#6C3BFF]" />
  }

  if (direction === 'desc') {
    return <ArrowDown size={14} className="text-[#6C3BFF]" />
  }

  return <ArrowUpDown size={14} className="text-slate-400" />
}

function SortableHeader({
  label,
  sortKey,
  currentSorts,
  onSort,
  align = 'left',
}: {
  label: string
  sortKey: SortKey
  currentSorts: SortItem[]
  onSort: (key: SortKey, addToExisting: boolean) => void
  align?: 'left' | 'right'
}) {
  const current = currentSorts.find((item) => item.key === sortKey) ?? null
  const priority =
    currentSorts.findIndex((item) => item.key === sortKey) >= 0
      ? currentSorts.findIndex((item) => item.key === sortKey) + 1
      : null

  return (
    <th className={`px-3 py-3 font-medium ${align === 'right' ? 'text-right' : 'text-left'}`}>
      <button
        type="button"
        onClick={(event) => onSort(sortKey, event.shiftKey)}
        className={`inline-flex items-center gap-2 transition hover:text-slate-700 ${
          align === 'right' ? 'ml-auto' : ''
        }`}
        title="Click to sort. Shift+Click to add secondary sort."
      >
        <span>{label}</span>
        <SortIcon direction={current?.direction ?? null} />
        {priority ? (
          <span className="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#F2F0FF] px-1 text-[10px] font-semibold text-[#6C3BFF]">
            {priority}
          </span>
        ) : null}
      </button>
    </th>
  )
}

export default function OrdersPage() {
  const { setAsOfDate, setHeaderRange } = useOutletContext<DashboardOutletContext>()

  const [stats, setStats] = useState<OrdersStatsResponse | null>(null)
  const [orders, setOrders] = useState<OrdersListResponse | null>(null)

  const [statsLoading, setStatsLoading] = useState(true)
  const [tableLoading, setTableLoading] = useState(true)

  const [filters, setFilters] = useState<OrdersFilters>({
    search: '',
    status: '',
    channel: '',
    date_from: '',
    date_to: '',
    per_page: 25,
    sort: '-ordered_at',
  })

  const [draftFilters, setDraftFilters] = useState<OrdersFilters>({
    status: '',
    channel: '',
    date_from: '',
    date_to: '',
  })

  const [page, setPage] = useState(1)
  const [isFilterModalOpen, setIsFilterModalOpen] = useState(false)

  useEffect(() => {
    setAsOfDate(null)

    return () => {
      setHeaderRange(null)
    }
  }, [setAsOfDate, setHeaderRange])

  useEffect(() => {
    async function loadStats() {
      try {
        setStatsLoading(true)

        const response = await getOrdersStats({
          status: filters.status,
          channel: filters.channel,
          date_from: filters.date_from,
          date_to: filters.date_to,
        })

        setStats(response)

        setHeaderRange({
          startDate: response.summary_period.start_date,
          endDate: response.summary_period.end_date,
          label: response.summary_period.label,
        })
      } finally {
        setStatsLoading(false)
      }
    }

    loadStats()
  }, [filters.status, filters.channel, filters.date_from, filters.date_to, setHeaderRange])

  useEffect(() => {
    async function loadOrdersList() {
      try {
        setTableLoading(true)

        const response = await getOrders({
          ...filters,
          page,
        })

        setOrders(response)
      } finally {
        setTableLoading(false)
      }
    }

    loadOrdersList()
  }, [filters, page])

  const statusChartData = useMemo(() => {
    return (
      stats?.status_counts.map((item) => ({
        label: item.status,
        revenue: 0,
        orders: item.total_orders,
      })) ?? []
    )
  }, [stats])

  const trendCategories = stats?.trend.map((item) => item.label) ?? []
  const trendSeries = stats?.trend.map((item) => item.total_orders) ?? []

  const activeFilterCount = useMemo(() => {
    let count = 0
    if (filters.status) count += 1
    if (filters.channel) count += 1
    if (filters.date_from) count += 1
    if (filters.date_to) count += 1
    return count
  }, [filters])

  const currentSorts = useMemo(
    () => parseSortString(filters.sort),
    [filters.sort]
  )

  function updateSearch(value: string) {
    setPage(1)
    setFilters((prev) => ({
      ...prev,
      search: value,
    }))
  }

  function updateDraftFilter<K extends keyof OrdersFilters>(key: K, value: OrdersFilters[K]) {
    setDraftFilters((prev) => ({
      ...prev,
      [key]: value,
    }))
  }

  function updatePerPage(value: number) {
    setPage(1)
    setFilters((prev) => ({
      ...prev,
      per_page: value,
    }))
  }

  function handleSort(key: SortKey, addToExisting: boolean) {
  setPage(1)

  const existing = parseSortString(filters.sort)
  const index = existing.findIndex((item) => item.key === key)

  let next: SortItem[] = []

  if (addToExisting) {
    next = [...existing]

    if (index >= 0) {
      const current = next[index]

      if (current.direction === 'asc') {
        next[index] = { ...current, direction: 'desc' }
      } else {
        next.splice(index, 1)
      }
    } else {
      next.push({ key, direction: 'asc' })
    }

    next = next.slice(0, 2)
  } else {
    if (index === -1) {
      next = [{ key, direction: 'asc' }]
    } else {
      const current = existing[index]

      if (current.direction === 'asc') {
        next = [{ key, direction: 'desc' }]
      } else {
        next = []
      }
    }
  }

  setFilters((prev) => ({
    ...prev,
    sort: stringifySorts(next),
  }))
}

  function openFilters() {
    setDraftFilters({
      status: filters.status ?? '',
      channel: filters.channel ?? '',
      date_from: filters.date_from ?? '',
      date_to: filters.date_to ?? '',
    })

    setIsFilterModalOpen(true)
  }

  function applyFilters() {
    setPage(1)
    setFilters((prev) => ({
      ...prev,
      status: draftFilters.status ?? '',
      channel: draftFilters.channel ?? '',
      date_from: draftFilters.date_from ?? '',
      date_to: draftFilters.date_to ?? '',
    }))
    setIsFilterModalOpen(false)
  }

  function resetFilters() {
    setPage(1)

    const resetValues: OrdersFilters = {
      search: '',
      status: '',
      channel: '',
      date_from: '',
      date_to: '',
      per_page: 25,
      sort: '-ordered_at',
    }

    setFilters(resetValues)
    setDraftFilters({
      status: '',
      channel: '',
      date_from: '',
      date_to: '',
    })
    setIsFilterModalOpen(false)
  }

  return (
    <div className="space-y-8">
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
        <StatCard
          title="Total Orders"
          value={statsLoading || !stats ? '...' : formatNumber(stats.summary.total_orders)}
          secondaryValue=""
          compact
        />

        <StatCard
          title="Pending Orders"
          value={statsLoading || !stats ? '...' : formatNumber(stats.summary.pending_orders)}
          secondaryValue="Awaiting action"
          compact
        />

        <StatCard
          title="Delivered Orders"
          value={statsLoading || !stats ? '...' : formatNumber(stats.summary.delivered_orders)}
          secondaryValue=""
          compact
        />

        <StatCard
          title="Cancelled Orders"
          value={statsLoading || !stats ? '...' : formatNumber(stats.summary.cancelled_orders)}
          secondaryValue=""
          compact
        />

        <StatCard
          title="Total Order Value"
          value={
            statsLoading || !stats
              ? '...'
              : formatCompactNumber(stats.summary.total_value)
          }
          secondaryValue={
            statsLoading || !stats
              ? ''
              : formatCurrency(stats.summary.total_value)
          }
          compact
        />

        <StatCard
          title="Refund Value"
          value={
            statsLoading || !stats
              ? '...'
              : formatCompactNumber(stats.summary.refund_value)
          }
          secondaryValue={
            statsLoading || !stats
              ? ''
              : `${formatCurrency(stats.summary.refund_value)} · ${formatNumber(
                  stats.summary.refund_count
                )} refunds`
          }
          compact
        />
      </div>

      <div className="grid gap-6 xl:grid-cols-2">
        <SectionCard title="Orders by Status">
          <DonutChart items={statusChartData} metric="orders" />
        </SectionCard>

        <SectionCard title="Orders Trend">
          <OrdersTrendChart
            categories={trendCategories}
            data={trendSeries}
          />
          <p className="mt-2 text-xs text-slate-400 md:hidden">
            Swipe horizontally to view full chart
          </p>
        </SectionCard>
      </div>

      <SectionCard title="Orders">
        <div className="space-y-5">
          <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div className="relative w-full lg:max-w-md">
              <Search
                size={16}
                className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"
              />
              <input
                type="text"
                value={filters.search ?? ''}
                onChange={(e) => updateSearch(e.target.value)}
                placeholder="Search order number, customer, sales rep..."
                className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-11 pr-4 text-sm text-slate-900 outline-none transition focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/10"
              />
            </div>

            <div className="flex flex-wrap items-center gap-3">
              <div className="flex items-center gap-2">
                <label className="text-sm font-medium text-slate-600">
                  Page Size
                </label>
                <select
                  value={filters.per_page ?? 25}
                  onChange={(e) => updatePerPage(Number(e.target.value))}
                  className="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/10"
                >
                  <option value={10}>10</option>
                  <option value={25}>25</option>
                  <option value={50}>50</option>
                  <option value={100}>100</option>
                </select>
              </div>

              {activeFilterCount > 0 ? (
                <span className="inline-flex items-center rounded-full bg-[#F2F0FF] px-3 py-1 text-xs font-semibold text-[#6C3BFF]">
                  {activeFilterCount} active filter{activeFilterCount > 1 ? 's' : ''}
                </span>
              ) : null}

              <button
                type="button"
                onClick={openFilters}
                className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
              >
                <Filter size={16} />
                Filters
              </button>

              <button
                type="button"
                onClick={resetFilters}
                className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
              >
                Reset
              </button>
            </div>
          </div>

          <div className="flex items-center justify-between gap-3 text-xs text-slate-400">
            <p>Click a column to sort. Shift+Click to add a second sort.</p>
          </div>

          <div className="overflow-x-auto">
            <table className="min-w-full text-sm">
              <thead>
                <tr className="border-b border-slate-200 text-left text-slate-500">
                  <SortableHeader
                    label="Order No"
                    sortKey="order_number"
                    currentSorts={currentSorts}
                    onSort={handleSort}
                  />
                  <SortableHeader
                    label="Date"
                    sortKey="ordered_at"
                    currentSorts={currentSorts}
                    onSort={handleSort}
                  />
                  <SortableHeader
                    label="Customer"
                    sortKey="customer_name"
                    currentSorts={currentSorts}
                    onSort={handleSort}
                  />
                  <SortableHeader
                    label="Channel"
                    sortKey="channel"
                    currentSorts={currentSorts}
                    onSort={handleSort}
                  />
                  <SortableHeader
                    label="Status"
                    sortKey="status"
                    currentSorts={currentSorts}
                    onSort={handleSort}
                  />
                  <SortableHeader
                    label="Total"
                    sortKey="grand_total"
                    currentSorts={currentSorts}
                    onSort={handleSort}
                    align="right"
                  />
                  <SortableHeader
                    label="Sales Rep"
                    sortKey="employee_name"
                    currentSorts={currentSorts}
                    onSort={handleSort}
                  />
                </tr>
              </thead>

              <tbody>
                {tableLoading ? (
                  <tr>
                    <td colSpan={7} className="px-3 py-8 text-center text-slate-500">
                      Loading orders...
                    </td>
                  </tr>
                ) : !orders || orders.data.length === 0 ? (
                  <tr>
                    <td colSpan={7} className="px-3 py-8 text-center text-slate-500">
                      No orders found.
                    </td>
                  </tr>
                ) : (
                  orders.data.map((order) => (
                    <tr key={order.id} className="border-b border-slate-100">
                      <td className="px-3 py-3 font-medium text-slate-900">
                        {order.order_number}
                      </td>

                      <td className="px-3 py-3 text-slate-600">
                        {order.ordered_at
                          ? new Date(order.ordered_at).toLocaleDateString('en-US', {
                              month: 'short',
                              day: 'numeric',
                              year: 'numeric',
                            })
                          : '-'}
                      </td>

                      <td className="px-3 py-3 text-slate-900">
                        {order.customer_name}
                      </td>

                      <td className="px-3 py-3 text-slate-600">
                        {formatLabel(order.channel ?? 'unknown')}
                      </td>

                      <td className="px-3 py-3">
                        <span
                          className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ${statusClasses(
                            order.status
                          )}`}
                        >
                          {formatLabel(order.status)}
                        </span>
                      </td>

                      <td className="px-3 py-3 text-right font-medium text-slate-900">
                        {formatCurrency(order.grand_total)}
                      </td>

                      <td className="px-3 py-3 text-slate-600">
                        {order.employee_name}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>

          {orders ? (
            <div className="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
              <p className="text-sm text-slate-500">
                Showing {orders.meta.from ?? 0} to {orders.meta.to ?? 0} of{' '}
                {formatNumber(orders.meta.total)} orders
              </p>

              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={() => setPage((prev) => Math.max(prev - 1, 1))}
                  disabled={orders.meta.current_page === 1}
                  className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  Previous
                </button>

                <span className="text-sm text-slate-500">
                  Page {orders.meta.current_page} of {orders.meta.last_page}
                </span>

                <button
                  type="button"
                  onClick={() =>
                    setPage((prev) => Math.min(prev + 1, orders.meta.last_page))
                  }
                  disabled={orders.meta.current_page === orders.meta.last_page}
                  className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  Next
                </button>
              </div>
            </div>
          ) : null}
        </div>
      </SectionCard>

      {isFilterModalOpen ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 px-4">
          <div className="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div className="flex items-center justify-between border-b border-slate-200 px-6 py-4">
              <div>
                <h3 className="text-lg font-semibold text-slate-900">Filters</h3>
                <p className="mt-1 text-sm text-slate-500">
                  Refine orders stats and table results
                </p>
              </div>

              <button
                type="button"
                onClick={() => setIsFilterModalOpen(false)}
                className="rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50"
              >
                <X size={18} />
              </button>
            </div>

            <div className="space-y-5 px-6 py-5">
              <div className="grid gap-4 md:grid-cols-2">
                <div>
                  <label className="mb-2 block text-sm font-medium text-slate-600">
                    Status
                  </label>
                  <select
                    value={draftFilters.status ?? ''}
                    onChange={(e) => updateDraftFilter('status', e.target.value)}
                    className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/10"
                  >
                    <option value="">All</option>
                    {STATUS_OPTIONS.map((status) => (
                      <option key={status} value={status}>
                        {formatLabel(status)}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="mb-2 block text-sm font-medium text-slate-600">
                    Channel
                  </label>
                  <select
                    value={draftFilters.channel ?? ''}
                    onChange={(e) => updateDraftFilter('channel', e.target.value)}
                    className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/10"
                  >
                    <option value="">All</option>
                    {CHANNEL_OPTIONS.map((channel) => (
                      <option key={channel} value={channel}>
                        {formatLabel(channel)}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="mb-2 block text-sm font-medium text-slate-600">
                    Date From
                  </label>
                  <input
                    type="date"
                    value={draftFilters.date_from ?? ''}
                    onChange={(e) => updateDraftFilter('date_from', e.target.value)}
                    className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/10"
                  />
                </div>

                <div>
                  <label className="mb-2 block text-sm font-medium text-slate-600">
                    Date To
                  </label>
                  <input
                    type="date"
                    value={draftFilters.date_to ?? ''}
                    onChange={(e) => updateDraftFilter('date_to', e.target.value)}
                    className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none transition focus:border-[#7C3AED] focus:ring-2 focus:ring-[#7C3AED]/10"
                  />
                </div>
              </div>
            </div>

            <div className="flex items-center justify-between border-t border-slate-200 px-6 py-4">
              <button
                type="button"
                onClick={resetFilters}
                className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
              >
                Reset
              </button>

              <div className="flex items-center gap-3">
                <button
                  type="button"
                  onClick={() => setIsFilterModalOpen(false)}
                  className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                  Cancel
                </button>

                <button
                  type="button"
                  onClick={applyFilters}
                  className="rounded-xl bg-[#6C3BFF] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#5B2EFF]"
                >
                  Apply Filters
                </button>
              </div>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  )
}