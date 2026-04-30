import { useEffect, useMemo, useState } from 'react'
import { Building2, Eye, UserRound, X } from 'lucide-react'
import { useOutletContext } from 'react-router-dom'
import ChartToggle from '../../components/dashboard/ChartToggle'
import SectionCard from '../../components/dashboard/SectionCard'
import StatCard from '../../components/dashboard/StatCard'
import { getCustomerDetail, getCustomers } from '../../api/customers'
import type { DashboardOutletContext } from '../../layouts/DashboardLayout'
import type {
  CustomerDetailResponse,
  CustomerPagination,
  CustomerRow,
  CustomerType,
  CustomersResponse,
} from '../../types/customers'
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
    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
        Showing <span className="font-data font-semibold">{pagination.from}</span> to{' '}
        <span className="font-data font-semibold">{pagination.to}</span> of{' '}
        <span className="font-data font-semibold">{pagination.total}</span> customers
      </div>

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
                className={[
                  'inline-flex h-10 min-w-10 items-center justify-center text-sm font-semibold',
                  isDark ? 'text-slate-600' : 'text-slate-400',
                ].join(' ')}
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
        'inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1',
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
  }, [customerType])

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
  ])

  async function openCustomer(customer: CustomerRow) {
    try {
      setDetailLoading(true)
      setDetailError(null)
      setSelectedCustomer(null)

      const response = await getCustomerDetail(customer.id)

      setSelectedCustomer(response)
    } catch {
      setDetailError('Failed to load customer details.')
    } finally {
      setDetailLoading(false)
    }
  }

  const filterOptions = useMemo(
    () =>
      data?.filterOptions ?? {
        states: [],
        statuses: ['active', 'inactive'],
      },
    [data]
  )

  const inputClass = [
    'h-10 rounded-xl border px-3 text-sm outline-none transition',
    isDark
      ? 'border-slate-800 bg-slate-950 text-slate-200 placeholder:text-slate-600 focus:border-blue-500'
      : 'border-slate-200 bg-white text-slate-700 placeholder:text-slate-400 focus:border-blue-500',
  ].join(' ')

  const selectedTypeLabel = customerType === 'individual' ? 'Individuals' : 'Businesses'
  const selectedTypeSingular = customerType === 'individual' ? 'Individual' : 'Business'
  const clvTitle = customerType === 'individual' ? 'Average Customer CLV' : 'Average Account CLV'

  return (
    <div className="space-y-8">
      <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 className={`text-xl font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
            Customers
          </h1>
          <p className={`mt-1 text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
            Customer geography, CLV, and lifetime sales behavior.
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

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <StatCard
          title={`Total ${selectedTypeLabel}`}
          value={data ? formatNumber(data.summary.totalCustomers) : '-'}
          helperText="Customer records"
          icon={customerType === 'individual' ? <UserRound size={18} /> : <Building2 size={18} />}
          compact
          variant={theme}
        />

        <StatCard
          title="Repeat Buyers"
          value={data ? formatNumber(data.summary.repeatCustomers) : '-'}
          helperText="2 or more delivered orders"
          compact
          variant={theme}
        />

        <StatCard
          title={clvTitle}
          value={data ? formatCompactNumber(data.summary.averageClv) : '-'}
          secondaryValue={data ? formatCurrency(data.summary.averageClv) : undefined}
          helperText={
            data
              ? `${data.summary.clvAssumptionYears}-year estimated value`
              : 'Estimated value'
          }
          compact
          monoSecondary
          variant={theme}
        />

        <StatCard
  title="Lifetime Revenue"
  value={data ? formatCompactNumber(data.summary.customerRevenue) : '-'}
  secondaryValue={data ? formatCurrency(data.summary.customerRevenue) : undefined}
  helperText={
    customerType === 'individual'
      ? 'Individual customers only'
      : 'Business customers only'
  }
  compact
  monoSecondary
  variant={theme}
/>

        <StatCard
          title="Average Order Value"
          value={data ? formatCurrency(data.summary.averageOrderValue) : '-'}
          helperText="Lifetime delivered orders"
          compact
          monoSecondary
          variant={theme}
        />
      </div>

      <SectionCard
        title={`${selectedTypeSingular} Customer Distribution`}
        description="Customer concentration across the United States."
        variant={theme}
      >
        <CustomerStateMap
          data={data?.stateDistribution ?? []}
          variant={theme}
        />
      </SectionCard>

      <SectionCard
        title={customerType === 'individual' ? 'Individual Customers' : 'Business Customers'}
        description="Search, filter, and review customer performance."
        variant={theme}
      >
        <div className="space-y-5">
          <div className="grid gap-3 xl:grid-cols-[minmax(260px,1fr)_180px_150px_140px_auto]">
            <FilterLabel label="Search" variant={theme}>
              <input
                type="search"
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
                onClick={() => {
                  setSearch('')
                  setSelectedState('all')
                  setStatus('all')
                  setPage(1)
                }}
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

          <div
            className={[
              'overflow-hidden rounded-2xl border',
              isDark ? 'border-slate-800' : 'border-slate-200',
            ].join(' ')}
          >
            <div className="overflow-x-auto">
              <table className="min-w-[1120px] w-full border-collapse">
                <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
                  <tr>
                    {[
                      customerType === 'individual' ? 'Customer' : 'Business',
                      customerType === 'individual' ? 'Location' : 'Contact',
                      customerType === 'individual' ? 'Orders' : 'Location',
                      'Revenue',
                      'Average Order',
                      'Last Order',
                      'Status',
                      '',
                    ].map((heading) => (
                      <th
                        key={heading || 'actions'}
                        className={[
                          'px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.1em]',
                          ['Revenue', 'Average Order'].includes(heading) ? 'text-right' : '',
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
                        className={`px-4 py-12 text-center text-sm ${
                          isDark ? 'text-slate-400' : 'text-slate-500'
                        }`}
                      >
                        Loading customers...
                      </td>
                    </tr>
                  ) : error ? (
                    <tr>
                      <td
                        colSpan={8}
                        className={`px-4 py-12 text-center text-sm ${
                          isDark ? 'text-rose-300' : 'text-rose-600'
                        }`}
                      >
                        {error}
                      </td>
                    </tr>
                  ) : data?.customers.length ? (
                    data.customers.map((customer) => (
                      <CustomerTableRow
                        key={customer.id}
                        customer={customer}
                        customerType={customerType}
                        variant={theme}
                        onView={() => openCustomer(customer)}
                      />
                    ))
                  ) : (
                    <tr>
                      <td
                        colSpan={8}
                        className={`px-4 py-12 text-center text-sm ${
                          isDark ? 'text-slate-400' : 'text-slate-500'
                        }`}
                      >
                        No customers found for the selected filters.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>

          {data?.pagination ? (
            <PaginationControls
              pagination={data.pagination}
              onPageChange={setPage}
              variant={theme}
            />
          ) : null}
        </div>
      </SectionCard>

      {detailLoading || detailError || selectedCustomer ? (
        <CustomerDetailDrawer
          detail={selectedCustomer}
          loading={detailLoading}
          error={detailError}
          onClose={() => {
            setSelectedCustomer(null)
            setDetailError(null)
          }}
          variant={theme}
        />
      ) : null}
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
    <tr className={isDark ? 'hover:bg-slate-800/60' : 'hover:bg-slate-50'}>
      <td className="px-4 py-4">
        <div
          className={[
            'max-w-[280px] truncate text-sm font-semibold',
            isDark ? 'text-white' : 'text-slate-950',
          ].join(' ')}
        >
          {customer.displayName}
        </div>

        <div
          className={[
            'mt-1 text-xs',
            isDark ? 'text-slate-400' : 'text-slate-500',
          ].join(' ')}
        >
          {[customer.email, formatPhoneNumber(customer.phone)].filter(Boolean).join(' / ')}
        </div>
      </td>

      {customerType === 'business' ? (
        <td className="px-4 py-4">
          <div
            className={[
              'text-sm font-semibold',
              isDark ? 'text-white' : 'text-slate-950',
            ].join(' ')}
          >
            {customer.contactName || '-'}
          </div>
          <div
            className={[
              'mt-1 text-xs',
              isDark ? 'text-slate-500' : 'text-slate-400',
            ].join(' ')}
          >
            Primary contact
          </div>
        </td>
      ) : (
        <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
          {location || '-'}
        </td>
      )}

      {customerType === 'business' ? (
        <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
          {location || '-'}
        </td>
      ) : (
        <td className="px-4 py-4">
          <div
            className={[
              'font-data text-sm font-semibold',
              isDark ? 'text-white' : 'text-slate-950',
            ].join(' ')}
          >
            {formatNumber(customer.orderCount)}
          </div>
          <div
            className={[
              'mt-1 text-xs',
              isDark ? 'text-slate-500' : 'text-slate-400',
            ].join(' ')}
          >
            orders
          </div>
        </td>
      )}

      <td
        className={[
          'px-4 py-4 text-right font-data text-sm font-semibold',
          isDark ? 'text-white' : 'text-slate-950',
        ].join(' ')}
      >
        {formatCurrency(customer.totalRevenue)}
      </td>

      <td
        className={[
          'px-4 py-4 text-right font-data text-sm',
          isDark ? 'text-slate-300' : 'text-slate-600',
        ].join(' ')}
      >
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
          <Eye size={15} />
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
  onClose,
  variant,
}: {
  detail: CustomerDetailResponse | null
  loading: boolean
  error: string | null
  onClose: () => void
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'
  const customer = detail?.customer

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
              {customer?.displayName ?? 'Customer Details'}
            </h2>
            <p className={`mt-1 text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
              {customer?.type === 'business'
                ? 'Business profile and lifetime sales history'
                : 'Customer profile and lifetime sales history'}
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
              Loading customer details...
            </div>
          ) : error ? (
            <div className={`py-20 text-center text-sm ${isDark ? 'text-rose-300' : 'text-rose-600'}`}>
              {error}
            </div>
          ) : detail ? (
            <CustomerDetailContent detail={detail} variant={variant} />
          ) : null}
        </div>
      </div>
    </div>
  )
}

function DetailCard({
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
      <div
        className={[
          'mb-3 text-xs font-semibold uppercase tracking-[0.12em]',
          isDark ? 'text-slate-500' : 'text-slate-400',
        ].join(' ')}
      >
        {title}
      </div>

      {children}
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
        isDark ? 'border-slate-800 bg-slate-900' : 'border-slate-200 bg-slate-50',
      ].join(' ')}
    >
      <div
        className={[
          'text-xs font-semibold uppercase tracking-[0.12em]',
          isDark ? 'text-slate-500' : 'text-slate-400',
        ].join(' ')}
      >
        {label}
      </div>

      <div
        className={[
          'mt-2 font-data text-sm font-semibold',
          isDark ? 'text-white' : 'text-slate-950',
        ].join(' ')}
      >
        {value}
      </div>
    </div>
  )
}

function CustomerDetailContent({
  detail,
  variant,
}: {
  detail: CustomerDetailResponse
  variant: 'light' | 'dark'
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
    <div className="space-y-6">
      <div className="grid gap-4 xl:grid-cols-3">
        <DetailCard
          title={customer.type === 'business' ? 'Business Summary' : 'Customer Summary'}
          variant={variant}
        >
          <div className="space-y-1">
            <div className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
              {customer.displayName}
            </div>

            {customer.contactName ? (
              <div className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
                Contact: {customer.contactName}
              </div>
            ) : null}

            {customer.email ? (
              <div className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
                {customer.email}
              </div>
            ) : null}

            {customer.phone ? (
              <div className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
                {formatPhoneNumber(customer.phone)}
              </div>
            ) : null}
          </div>
        </DetailCard>

        <DetailCard title="Status" variant={variant}>
          <div className="space-y-3">
            <StatusBadge status={customer.status} variant={variant} />
            <div className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
              Type: {customer.type === 'business' ? 'Business' : 'Individual'}
            </div>
            <div className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
              Created: {formatDate(customer.createdAt)}
            </div>
          </div>
        </DetailCard>

        <DetailCard title="Address" variant={variant}>
          <div className={`text-sm leading-6 ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
            {address || 'No address available'}
          </div>
        </DetailCard>
      </div>

      <div className="grid gap-4 md:grid-cols-3 xl:grid-cols-5">
        <MetricBox label="Orders" value={formatNumber(detail.metrics.orderCount)} variant={variant} />
        <MetricBox label="Revenue" value={formatCurrency(detail.metrics.totalRevenue)} variant={variant} />
        <MetricBox label="Average Order" value={formatCurrency(detail.metrics.averageOrderValue)} variant={variant} />
        <MetricBox label="First Order" value={formatDate(detail.metrics.firstOrderAt)} variant={variant} />
        <MetricBox label="Last Order" value={formatDate(detail.metrics.lastOrderAt)} variant={variant} />
      </div>

      <div className="grid gap-4 xl:grid-cols-2">
        <DetailCard title="Top Product" variant={variant}>
          {detail.topProduct ? (
            <div className="space-y-1">
              <div className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
                {detail.topProduct.title}
              </div>
              <div className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
                {formatNumber(detail.topProduct.quantity)} units / {formatCurrency(detail.topProduct.totalValue)}
              </div>
            </div>
          ) : (
            <div className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
              No product data available.
            </div>
          )}
        </DetailCard>

        <DetailCard title="Favorite Channel" variant={variant}>
          {detail.favoriteChannel ? (
            <div className="space-y-1">
              <div className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
                {formatEnumLabel(detail.favoriteChannel.channel)}
              </div>
              <div className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
                {formatNumber(detail.favoriteChannel.orderCount)} orders / {formatCurrency(detail.favoriteChannel.revenue)}
              </div>
            </div>
          ) : (
            <div className={`text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
              No channel data available.
            </div>
          )}
        </DetailCard>
      </div>

      <div>
        <h3 className={`mb-3 text-sm font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
          Recent Orders
        </h3>

        <div
          className={[
            'overflow-hidden rounded-2xl border',
            isDark ? 'border-slate-800' : 'border-slate-200',
          ].join(' ')}
        >
          <div className="overflow-x-auto">
            <table className="min-w-[780px] w-full border-collapse">
              <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
                <tr>
                  {['Order', 'Date', 'Channel', 'Status', 'Payment', 'Total'].map((heading) => (
                    <th
                      key={heading}
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
                {detail.recentOrders.length ? (
                  detail.recentOrders.map((order) => (
                    <tr key={order.id}>
                      <td className="px-4 py-4 font-data text-sm font-semibold">
                        {order.orderNumber}
                      </td>
                      <td className="px-4 py-4 text-sm">{formatDate(order.orderedAt)}</td>
                      <td className="px-4 py-4 text-sm">{formatEnumLabel(order.channel)}</td>
                      <td className="px-4 py-4 text-sm">{formatEnumLabel(order.status)}</td>
                      <td className="px-4 py-4 text-sm">{formatEnumLabel(order.paymentStatus)}</td>
                      <td className="px-4 py-4 text-right font-data text-sm font-semibold">
                        {formatCurrency(order.totalValue)}
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td
                      colSpan={6}
                      className={`px-4 py-10 text-center text-sm ${
                        isDark ? 'text-slate-400' : 'text-slate-500'
                      }`}
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