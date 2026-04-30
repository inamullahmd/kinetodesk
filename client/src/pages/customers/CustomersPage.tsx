import { useEffect, useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import { Building2, Eye, UserRound } from 'lucide-react'
import { useOutletContext } from 'react-router-dom'
import ChartToggle from '../../components/dashboard/ChartToggle'
import SectionCard from '../../components/dashboard/SectionCard'
import StatCard from '../../components/dashboard/StatCard'
import RightDrawer from '../../components/common/RightDrawer'
import SortableHeader from '../../components/common/SortableHeader'
import DedicatedEntitySearch from '../../components/search/DedicatedEntitySearch'
import { getCustomerDetail, getCustomers } from '../../api/customers'
import { getOrderDetail } from '../../api/orders'
import type { DashboardOutletContext } from '../../layouts/DashboardLayout'
import type {
  CustomerDetailResponse,
  CustomerPagination,
  CustomerRecentOrder,
  CustomerRow,
  CustomerType,
  CustomersResponse,
} from '../../types/customers'
import type { LookupResult } from '../../types/lookup'
import type { OrderDetail, SalesOrderDetailItem } from '../../types/orders'
import type { SortItem } from '../../types/sort'
import { useMultiSort } from '../../hooks/useMultiSort'
import {
  formatCompactNumber,
  formatCurrency,
  formatNumber,
} from '../../utils/format'
import CustomerStateMap from './CustomerStateMap'

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
  pagination,
  onPageChange,
  variant,
}: {
  pagination: CustomerPagination
  onPageChange: (page: number) => void
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'
  const pageItems = getPaginationItems(pagination.currentPage, pagination.lastPage)

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
        <span className="font-semibold">{pagination.total}</span> customers
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

function StatusBadge({
  status,
  variant,
}: {
  status: string
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'
  const active = status === 'active'

  return (
    <span
      className={[
        'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',
        active
          ? isDark
            ? 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/20'
            : 'bg-emerald-50 text-emerald-700 ring-emerald-200'
          : isDark
            ? 'bg-slate-800 text-slate-300 ring-slate-700'
            : 'bg-slate-100 text-slate-600 ring-slate-200',
      ].join(' ')}
    >
      {formatEnumLabel(status)}
    </span>
  )
}

export default function CustomersPage() {
  const {
    setHeaderRange,
    setHeaderDateRangeControl,
    theme,
  } = useOutletContext<DashboardOutletContext>()

  const [customerType, setCustomerType] = useState<CustomerType>('individual')
  const [search, setSearch] = useState('')
  const [selectedState, setSelectedState] = useState('all')
  const [status, setStatus] = useState('all')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(10)
  const [data, setData] = useState<CustomersResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const [selectedCustomer, setSelectedCustomer] =
    useState<CustomerDetailResponse | null>(null)
  const [detailLoading, setDetailLoading] = useState(false)
  const [detailError, setDetailError] = useState<string | null>(null)

  const [selectedOrder, setSelectedOrder] = useState<OrderDetail | null>(null)
  const [orderDetailLoading, setOrderDetailLoading] = useState(false)
  const [orderDetailError, setOrderDetailError] = useState<string | null>(null)

  const {
    sortParam,
    cycleSort,
    getSort,
    getSortIndex,
    setSorts,
  } = useMultiSort(
    [{ field: 'displayName', direction: 'asc' }],
    { onChange: () => setPage(1) },
  )

  const isDark = theme === 'dark'

  useEffect(() => {
    setHeaderRange(null)
    setHeaderDateRangeControl(null)

    return () => {
      setHeaderRange(null)
      setHeaderDateRangeControl(null)
    }
  }, [setHeaderRange, setHeaderDateRangeControl])

  useEffect(() => {
    setSearch('')
    setSelectedState('all')
    setStatus('all')
    setPage(1)
    setSelectedCustomer(null)
    setSelectedOrder(null)
    setSorts([{ field: 'displayName', direction: 'asc' }])
  }, [customerType, setSorts])

  useEffect(() => {
    let active = true

    async function loadCustomers() {
      try {
        setLoading(true)
        setError(null)

        const response = await getCustomers({
          customerType,
          search,
          state: selectedState,
          status,
          page,
          perPage,
          sort: sortParam,
        })

        if (!active) return
        setData(response)
      } catch {
        if (!active) return
        setError('Failed to load customers.')
      } finally {
        if (active) {
          setLoading(false)
        }
      }
    }

    loadCustomers()

    return () => {
      active = false
    }
  }, [
    customerType,
    search,
    selectedState,
    status,
    page,
    perPage,
    sortParam,
  ])

  async function openCustomerById(id: number) {
    try {
      setDetailLoading(true)
      setDetailError(null)
      setSelectedCustomer(null)
      setSelectedOrder(null)

      const response = await getCustomerDetail(id)
      setSelectedCustomer(response)
    } catch {
      setDetailError('Failed to load customer details.')
    } finally {
      setDetailLoading(false)
    }
  }

  function openCustomer(customer: CustomerRow) {
    void openCustomerById(customer.id)
  }

  function openLookupCustomer(result: LookupResult) {
    if (result.type === 'customer') {
      void openCustomerById(result.id)
    }
  }

  async function openSalesOrderById(id: number) {
    try {
      setOrderDetailLoading(true)
      setOrderDetailError(null)
      setSelectedOrder(null)

      setSelectedCustomer(null)
      setDetailError(null)

      const response = await getOrderDetail('sales', id)
      setSelectedOrder(response)
    } catch {
      setOrderDetailError('Failed to load sales order details.')
    } finally {
      setOrderDetailLoading(false)
    }
  }

  function resetFilters() {
    setSearch('')
    setSelectedState('all')
    setStatus('all')
    setPage(1)
  }

  const filterOptions = useMemo(
    () =>
      data?.filterOptions ?? {
        states: [],
        statuses: ['active', 'inactive'],
      },
    [data],
  )

  const inputClass = [
    'h-10 rounded-xl border px-3 text-sm outline-none transition',
    isDark
      ? 'border-slate-800 bg-slate-950 text-slate-200 placeholder:text-slate-600 focus:border-blue-500'
      : 'border-slate-200 bg-white text-slate-700 placeholder:text-slate-400 focus:border-blue-500',
  ].join(' ')

  const selectedTypeLabel = customerType === 'individual' ? 'Individual Customers' : 'Business Customers'
  const selectedTypeSingular = customerType === 'individual' ? 'Individual' : 'Business'
  const revenueTitle = customerType === 'individual' ? 'Individual Revenue' : 'Business Revenue'
  const clvTitle = customerType === 'individual' ? 'Average Customer CLV' : 'Average Account CLV'

  return (
    <div className="space-y-6">
      <div className="flex flex-col justify-between gap-4 xl:flex-row xl:items-end">
        <div>
          <h1 className={`text-3xl font-semibold tracking-tight ${isDark ? 'text-white' : 'text-slate-950'}`}>
            Customers
          </h1>
          <p className={`mt-1 text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
            Customer geography, lifetime sales behavior, CLV, and direct customer lookup.
          </p>
        </div>

        <ChartToggle
          value={customerType}
          onChange={(value) => setCustomerType(value as CustomerType)}
          options={[
            { label: 'Individuals', value: 'individual' },
            { label: 'Businesses', value: 'business' },
          ]}
          variant={theme}
        />
      </div>

      <DedicatedEntitySearch
  entity="customers"
  placeholder={
    customerType === 'individual'
      ? 'Find individual customer by name, email, phone, or city...'
      : 'Find business by name, contact, email, phone, or city...'
  }
  onOpenResult={openLookupCustomer}
  variant={theme}
/>

      <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
        <StatCard
          title={selectedTypeLabel}
          value={formatCompactNumber(data?.summary.totalCustomers ?? 0)}
          helperText={`${selectedTypeSingular} customer records`}
          compact
          variant={theme}
        />

        <StatCard
          title="Repeat Customers"
          value={formatCompactNumber(data?.summary.repeatCustomers ?? 0)}
          helperText="Customers with more than one SO"
          compact
          variant={theme}
        />

        <StatCard
          title={revenueTitle}
          value={formatCurrency(data?.summary.customerRevenue ?? 0)}
          helperText={`Revenue from ${selectedTypeLabel.toLowerCase()}`}
          compact
          variant={theme}
        />

        <StatCard
          title="Average Order Value"
          value={formatCurrency(data?.summary.averageOrderValue ?? 0)}
          helperText="Revenue per sales order"
          compact
          variant={theme}
        />

        <StatCard
          title={clvTitle}
          value={formatCurrency(data?.summary.averageClv ?? 0)}
          helperText={`Estimated using ${data?.summary.clvAssumptionYears ?? 3} year lifespan`}
          compact
          variant={theme}
        />
      </div>

      <div className="space-y-6">
        <SectionCard
          title={`${selectedTypeLabel} List`}
          description="Table search filters the current customer list. Direct lookup above searches all customers globally."
          variant={theme}
        >
          <div className="mb-4 grid grid-cols-1 gap-3 lg:grid-cols-5">
            <FilterLabel label="Search" variant={theme}>
              <input
                value={search}
                onChange={(event) => {
                  setSearch(event.target.value)
                  setPage(1)
                }}
                placeholder={
                  customerType === 'individual'
                    ? 'Name, email, phone, city...'
                    : 'Business, contact, email, phone...'
                }
                className={inputClass}
              />
            </FilterLabel>

            <FilterLabel label="State" variant={theme}>
              <select
                value={selectedState}
                onChange={(event) => {
                  setSelectedState(event.target.value)
                  setPage(1)
                }}
                className={inputClass}
              >
                <option value="all">All states</option>
                {filterOptions.states.map((state) => (
                  <option key={state.code} value={state.code}>
                    {state.name}
                  </option>
                ))}
              </select>
            </FilterLabel>

            <FilterLabel label="Status" variant={theme}>
              <select
                value={status}
                onChange={(event) => {
                  setStatus(event.target.value)
                  setPage(1)
                }}
                className={inputClass}
              >
                <option value="all">All statuses</option>
                {filterOptions.statuses.map((item) => (
                  <option key={item} value={item}>
                    {formatEnumLabel(item)}
                  </option>
                ))}
              </select>
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

          <CustomerTable
            customers={data?.customers ?? []}
            loading={loading}
            error={error}
            customerType={customerType}
            variant={theme}
            onView={openCustomer}
            getSort={getSort}
            getSortIndex={getSortIndex}
            onSort={cycleSort}
          />

          {data?.pagination ? (
            <div className="mt-4">
              <PaginationControls
                pagination={data.pagination}
                onPageChange={setPage}
                variant={theme}
              />
            </div>
          ) : null}
        </SectionCard>

        <SectionCard
          title="Customer Geography"
          description="State-level customer concentration, order volume, and revenue distribution."
          variant={theme}
        >
          <div className="min-h-[360px]">
            <CustomerStateMap
              data={data?.stateDistribution ?? []}
              variant={theme}
            />
          </div>
        </SectionCard>
      </div>

      <CustomerDetailDrawer
        detail={selectedCustomer}
        loading={detailLoading}
        error={detailError}
        open={detailLoading || Boolean(detailError) || Boolean(selectedCustomer)}
        onClose={() => {
          setSelectedCustomer(null)
          setDetailError(null)
        }}
        onViewOrder={(order) => {
          void openSalesOrderById(order.id)
        }}
        variant={theme}
      />

      <SalesOrderDrawer
        order={selectedOrder}
        loading={orderDetailLoading}
        error={orderDetailError}
        open={orderDetailLoading || Boolean(orderDetailError) || Boolean(selectedOrder)}
        onClose={() => {
          setSelectedOrder(null)
          setOrderDetailError(null)
        }}
        variant={theme}
      />
    </div>
  )
}

function CustomerTable({
  customers,
  loading,
  error,
  customerType,
  variant,
  onView,
  getSort,
  getSortIndex,
  onSort,
}: {
  customers: CustomerRow[]
  loading: boolean
  error: string | null
  customerType: CustomerType
  variant: 'light' | 'dark'
  onView: (customer: CustomerRow) => void
  getSort: (field: string) => SortItem | undefined
  getSortIndex: (field: string) => number | undefined
  onSort: (field: string) => void
}) {
  const isDark = variant === 'dark'

  return (
    <div className="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[1180px] divide-y divide-slate-200 dark:divide-slate-800">
          <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
            <tr>
              <SortableHeader
                label={customerType === 'individual' ? 'Customer' : 'Business'}
                field="displayName"
                sort={getSort('displayName')}
                sortIndex={getSortIndex('displayName')}
                onSort={onSort}
              />
              <SortableHeader
                label={customerType === 'individual' ? 'Location' : 'Contact'}
                field={customerType === 'individual' ? 'location' : 'contactName'}
                sort={getSort(customerType === 'individual' ? 'location' : 'contactName')}
                sortIndex={getSortIndex(customerType === 'individual' ? 'location' : 'contactName')}
                onSort={onSort}
              />
              <SortableHeader
                label={customerType === 'individual' ? 'Orders' : 'Location'}
                field={customerType === 'individual' ? 'orderCount' : 'location'}
                sort={getSort(customerType === 'individual' ? 'orderCount' : 'location')}
                sortIndex={getSortIndex(customerType === 'individual' ? 'orderCount' : 'location')}
                onSort={onSort}
                align={customerType === 'individual' ? 'right' : 'left'}
              />
              <SortableHeader
                label="Revenue"
                field="totalRevenue"
                sort={getSort('totalRevenue')}
                sortIndex={getSortIndex('totalRevenue')}
                onSort={onSort}
                align="right"
              />
              <SortableHeader
                label="Average Order"
                field="averageOrderValue"
                sort={getSort('averageOrderValue')}
                sortIndex={getSortIndex('averageOrderValue')}
                onSort={onSort}
                align="right"
              />
              <SortableHeader
                label="Last Order"
                field="lastOrderAt"
                sort={getSort('lastOrderAt')}
                sortIndex={getSortIndex('lastOrderAt')}
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
            {loading ? (
              <tr>
                <td
                  colSpan={8}
                  className={`px-4 py-10 text-center text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}
                >
                  Loading customers...
                </td>
              </tr>
            ) : error ? (
              <tr>
                <td colSpan={8} className="px-4 py-10 text-center text-sm text-rose-600">
                  {error}
                </td>
              </tr>
            ) : customers.length ? (
              customers.map((customer) => (
                <CustomerTableRow
                  key={customer.id}
                  customer={customer}
                  customerType={customerType}
                  variant={variant}
                  onView={() => onView(customer)}
                />
              ))
            ) : (
              <tr>
                <td
                  colSpan={8}
                  className={`px-4 py-10 text-center text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}
                >
                  No customers found for the selected filters.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}

function CustomerTableRow({
  customer,
  customerType,
  variant,
  onView,
}: {
  customer: CustomerRow
  customerType: CustomerType
  variant: 'light' | 'dark'
  onView: () => void
}) {
  const isDark = variant === 'dark'
  const location = [customer.city, customer.stateCode ?? customer.state]
    .filter(Boolean)
    .join(', ')

  return (
    <tr className={isDark ? 'hover:bg-slate-950/70' : 'hover:bg-slate-50'}>
      <td className="px-4 py-4">
        <div className="flex items-start gap-3">
          <div
            className={[
              'mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl',
              customerType === 'business'
                ? isDark
                  ? 'bg-violet-500/10 text-violet-300'
                  : 'bg-violet-50 text-violet-700'
                : isDark
                  ? 'bg-blue-500/10 text-blue-300'
                  : 'bg-blue-50 text-blue-700',
            ].join(' ')}
          >
            {customerType === 'business' ? (
              <Building2 className="h-4 w-4" />
            ) : (
              <UserRound className="h-4 w-4" />
            )}
          </div>

          <div>
            <p className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
              {customer.displayName}
            </p>
            <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
              {[customer.email, formatPhoneNumber(customer.phone)].filter(Boolean).join(' / ') || '-'}
            </p>
          </div>
        </div>
      </td>

      <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
        {customerType === 'business' ? (
          <>
            <p className="font-medium">{customer.contactName || '-'}</p>
            <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
              Primary contact
            </p>
          </>
        ) : (
          location || '-'
        )}
      </td>

      <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
        {customerType === 'business' ? (
          location || '-'
        ) : (
          <>
            <p className="text-right font-semibold">{formatNumber(customer.orderCount)}</p>
            <p className={`text-right text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
              orders
            </p>
          </>
        )}
      </td>

      <td className={`px-4 py-4 text-right font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
        {formatCurrency(customer.totalRevenue)}
      </td>

      <td className={`px-4 py-4 text-right text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
        {formatCurrency(customer.averageOrderValue)}
      </td>

      <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
        {formatDate(customer.lastOrderAt)}
      </td>

      <td className="px-4 py-4">
        <StatusBadge status={customer.status} variant={variant} />
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

function CustomerDetailDrawer({
  detail,
  loading,
  error,
  open,
  onClose,
  onViewOrder,
  variant,
}: {
  detail: CustomerDetailResponse | null
  loading: boolean
  error: string | null
  open: boolean
  onClose: () => void
  onViewOrder: (order: CustomerRecentOrder) => void
  variant: 'light' | 'dark'
}) {
  const customer = detail?.customer

  return (
    <RightDrawer
      open={open}
      title={customer?.displayName ?? 'Customer Details'}
      subtitle={
        customer?.type === 'business'
          ? 'Business profile and lifetime sales history'
          : 'Individual customer profile and lifetime sales history'
      }
      onClose={onClose}
      widthClassName="max-w-5xl"
    >
      {loading ? (
        <div className="rounded-2xl bg-slate-50 p-5 text-sm text-slate-500">
          Loading customer details...
        </div>
      ) : error ? (
        <div className="rounded-2xl bg-rose-50 p-5 text-sm text-rose-700">
          {error}
        </div>
      ) : detail ? (
        <CustomerDetailContent
          detail={detail}
          variant={variant}
          onViewOrder={onViewOrder}
        />
      ) : null}
    </RightDrawer>
  )
}

function DetailCard({
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
    <div
      className={[
        'rounded-2xl border p-4',
        isDark ? 'border-slate-800 bg-slate-950' : 'border-slate-200 bg-slate-50',
      ].join(' ')}
    >
      <p className={`text-xs font-semibold uppercase tracking-wide ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
        {title}
      </p>
      <div className={`mt-3 text-sm ${isDark ? 'text-slate-300' : 'text-slate-700'}`}>
        {children}
      </div>
    </div>
  )
}

function MetricBox({
  label,
  value,
  variant,
}: {
  label: string
  value: string
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <div
      className={[
        'rounded-2xl border p-4',
        isDark ? 'border-slate-800 bg-slate-950' : 'border-slate-200 bg-slate-50',
      ].join(' ')}
    >
      <p className={`text-xs font-semibold uppercase tracking-wide ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
        {label}
      </p>
      <p className={`mt-1 font-data text-lg font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
        {value}
      </p>
    </div>
  )
}

function CustomerDetailContent({
  detail,
  variant,
  onViewOrder,
}: {
  detail: CustomerDetailResponse
  variant: 'light' | 'dark'
  onViewOrder: (order: CustomerRecentOrder) => void
}) {
  const isDark = variant === 'dark'
  const customer = detail.customer

  const address = [
    customer.addressLine1,
    customer.addressLine2,
    customer.city,
    customer.stateCode ?? customer.state,
    customer.postalCode,
    customer.country,
  ]
    .filter(Boolean)
    .join(', ')

  return (
    <div className="space-y-5">
      <div>
        <h2 className={`text-xl font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
          {customer.displayName}
        </h2>

        <div className={`mt-1 space-y-1 text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
          {customer.contactName ? <p>Contact: {customer.contactName}</p> : null}
          {customer.email ? <p>{customer.email}</p> : null}
          {customer.phone ? <p>{formatPhoneNumber(customer.phone)}</p> : null}
          <p>Type: {customer.type === 'business' ? 'Business' : 'Individual'}</p>
          <p>Created: {formatDate(customer.createdAt)}</p>
        </div>
      </div>

      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <MetricBox
          label="Orders"
          value={formatNumber(detail.metrics.orderCount)}
          variant={variant}
        />
        <MetricBox
          label="Total Revenue"
          value={formatCurrency(detail.metrics.totalRevenue)}
          variant={variant}
        />
        <MetricBox
          label="Average Order"
          value={formatCurrency(detail.metrics.averageOrderValue)}
          variant={variant}
        />
        <MetricBox
          label="Last Order"
          value={formatDate(detail.metrics.lastOrderAt)}
          variant={variant}
        />
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <DetailCard title="Address" variant={variant}>
          {address || 'No address available.'}
        </DetailCard>

        <DetailCard title="Top Product" variant={variant}>
          {detail.topProduct ? (
            <>
              <p className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
                {detail.topProduct.title}
              </p>
              <p>
                {formatNumber(detail.topProduct.quantity)} units /{' '}
                {formatCurrency(detail.topProduct.totalValue)}
              </p>
            </>
          ) : (
            'No product data available.'
          )}
        </DetailCard>

        <DetailCard title="Favorite Channel" variant={variant}>
          {detail.favoriteChannel ? (
            <>
              <p className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
                {formatEnumLabel(detail.favoriteChannel.channel)}
              </p>
              <p>
                {formatNumber(detail.favoriteChannel.orderCount)} orders /{' '}
                {formatCurrency(detail.favoriteChannel.revenue)}
              </p>
            </>
          ) : (
            'No channel data available.'
          )}
        </DetailCard>
      </div>

      <div>
        <h3 className={`mb-3 text-base font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
          Recent Sales Orders
        </h3>

        <div className="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
              <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
                <tr>
                  {['Order', 'Date', 'Channel', 'Status', 'Payment', 'Total', ''].map((heading) => (
                    <th
                      key={heading}
                      className="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                    >
                      {heading}
                    </th>
                  ))}
                </tr>
              </thead>

              <tbody
                className={`divide-y text-sm ${isDark
                    ? 'divide-slate-800 bg-slate-900 text-slate-300'
                    : 'divide-slate-100 bg-white text-slate-700'
                  }`}
              >
                {detail.recentOrders.length ? (
                  detail.recentOrders.map((order) => (
                    <tr key={order.id}>
                      <td className="px-3 py-3 font-semibold">{order.orderNumber}</td>
                      <td className="px-3 py-3">{formatDate(order.orderedAt)}</td>
                      <td className="px-3 py-3">{formatEnumLabel(order.channel)}</td>
                      <td className="px-3 py-3">{formatEnumLabel(order.status)}</td>
                      <td className="px-3 py-3">{formatEnumLabel(order.paymentStatus)}</td>
                      <td className="px-3 py-3 font-semibold">{formatCurrency(order.totalValue)}</td>
                      <td className="px-3 py-3 text-right">
                        <button
                          type="button"
                          onClick={() => onViewOrder(order)}
                          className={[
                            'inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-semibold transition',
                            isDark
                              ? 'border-slate-700 bg-slate-950 text-slate-200 hover:bg-slate-900'
                              : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
                          ].join(' ')}
                        >
                          <Eye className="h-3.5 w-3.5" />
                          View
                        </button>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td
                      colSpan={7}
                      className={`px-3 py-6 text-center ${isDark ? 'text-slate-500' : 'text-slate-400'}`}
                    >
                      No orders found.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  )
}

function SalesOrderDrawer({
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
      title={order?.orderNumber ?? 'Sales Order Details'}
      subtitle="Sales order detail opened from customer history"
      onClose={onClose}
      widthClassName="max-w-5xl"
    >
      {loading ? (
        <div className="rounded-2xl bg-slate-50 p-5 text-sm text-slate-500">
          Loading sales order details...
        </div>
      ) : error ? (
        <div className="rounded-2xl bg-rose-50 p-5 text-sm text-rose-700">
          {error}
        </div>
      ) : order ? (
        <SalesOrderDetailContent order={order} variant={variant} />
      ) : null}
    </RightDrawer>
  )
}

function SalesOrderDetailContent({
  order,
  variant,
}: {
  order: OrderDetail
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'
  const items = order.items as SalesOrderDetailItem[]

  return (
    <div className="space-y-5">
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <DetailCard title="Customer" variant={variant}>
          <p className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
            {order.counterpartyName}
          </p>
          {order.counterpartyEmail ? <p>{order.counterpartyEmail}</p> : null}
          {order.counterpartyPhone ? <p>{formatPhoneNumber(order.counterpartyPhone)}</p> : null}
        </DetailCard>

        <DetailCard title="Status" variant={variant}>
          <p>{formatEnumLabel(order.status)}</p>
          {order.paymentStatus ? <p>Payment: {formatEnumLabel(order.paymentStatus)}</p> : null}
          {order.channel ? <p>Channel: {formatEnumLabel(order.channel)}</p> : null}
        </DetailCard>

        <DetailCard title="Dates" variant={variant}>
          <p>Ordered: {formatDate(order.orderedAt)}</p>
          {order.completedAt ? <p>Completed: {formatDate(order.completedAt)}</p> : null}
        </DetailCard>
      </div>

      <div className="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <MetricBox label="Subtotal" value={formatCurrency(order.totals.subtotal)} variant={variant} />
        <MetricBox label="Discount" value={formatCurrency(order.totals.discountAmount)} variant={variant} />
        <MetricBox label="Tax" value={formatCurrency(order.totals.taxAmount)} variant={variant} />
        <MetricBox
          label="Shipping / Other"
          value={formatCurrency(Number(order.totals.shippingAmount) + Number(order.totals.otherAmount))}
          variant={variant}
        />
        <MetricBox label="Grand Total" value={formatCurrency(order.totals.grandTotal)} variant={variant} />
      </div>

      <div>
        <h3 className={`mb-3 text-base font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
          Line Items
        </h3>

        <div className="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
              <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
                <tr>
                  {[
                    'Product',
                    'Qty',
                    'Unit Price',
                    'Discount',
                    'Final Unit',
                    'Cost',
                    'Total',
                    'Profit',
                  ].map((heading) => (
                    <th
                      key={heading}
                      className="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                    >
                      {heading}
                    </th>
                  ))}
                </tr>
              </thead>

              <tbody
                className={`divide-y text-sm ${isDark
                    ? 'divide-slate-800 bg-slate-900 text-slate-300'
                    : 'divide-slate-100 bg-white text-slate-700'
                  }`}
              >
                {items.map((item) => (
                  <tr key={item.id}>
                    <td className="px-3 py-3">
                      <p className={`font-medium ${isDark ? 'text-white' : 'text-slate-950'}`}>
                        {item.productTitle}
                      </p>
                      <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
                        {[item.brandName, item.sku, item.modelNumber].filter(Boolean).join(' / ')}
                      </p>
                    </td>
                    <td className="px-3 py-3">{formatNumber(item.quantity)}</td>
                    <td className="px-3 py-3">{formatCurrency(item.unitPrice)}</td>
                    <td className="px-3 py-3">{formatCurrency(item.discountAmount)}</td>
                    <td className="px-3 py-3">{formatCurrency(item.finalUnitPrice)}</td>
                    <td className="px-3 py-3">{formatCurrency(item.costBasis)}</td>
                    <td className="px-3 py-3 font-semibold">{formatCurrency(item.lineTotal)}</td>
                    <td className="px-3 py-3 font-semibold">{formatCurrency(item.lineProfit)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  )
}