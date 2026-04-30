import { useEffect, useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import { BadgeDollarSign, Eye, UsersRound } from 'lucide-react'
import { useOutletContext } from 'react-router-dom'
import SectionCard from '../../shared/components/SectionCard'
import StatCard from '../../shared/components/StatCard'
import RightDrawer from '../../shared/components/RightDrawer'
import SortableHeader from '../../shared/components/SortableHeader'
import DedicatedEntitySearch from '../../shared/components/DedicatedEntitySearch'
import FilterDrawer from '../../shared/components/FilterDrawer'
import FilterToolbar from '../../shared/components/FilterToolbar'
import AppliedFilterChips from '../../shared/components/AppliedFilterChips'
import { getEmployeeDetail, getEmployees } from './directory.api'
import { getOrderDetail } from '../orders/orders.api'
import type { DashboardOutletContext } from '../../app/DashboardLayout'
import type { LookupResult } from '../../shared/types/lookup.types'
import type { OrderDetail, SalesOrderDetailItem } from '../orders/orders.types'
import type { SortItem } from '../../shared/types/sort.types'
import type {
  EmployeeDetailResponse,
  EmployeeRecentSalesOrder,
  EmployeeRow,
  EmployeesResponse,
} from './directory.types'
import { useMultiSort } from '../../shared/hooks/useMultiSort'
import {
  formatCurrency, formatNumber,
  formatDate,
  formatEnumLabel,
  formatPhoneNumber,
} from '../../shared/utils/format'
import {
  DetailCard,
  FilterLabel,
  MetricBox,
  PaginationControls,
  StatusBadge,
  TableEmptyState,
  WorkflowStatusBadge,
} from './directoryShared'

export default function EmployeesPage() {
  const { setHeaderRange, setHeaderDateRangeControl, theme } =
    useOutletContext<DashboardOutletContext>()

  const [search, setSearch] = useState('')
  const [role, setRole] = useState('all')
  const [status, setStatus] = useState('all')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(10)
  const [data, setData] = useState<EmployeesResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [filtersOpen, setFiltersOpen] = useState(false)

  const [selectedEmployee, setSelectedEmployee] =
    useState<EmployeeDetailResponse | null>(null)
  const [detailLoading, setDetailLoading] = useState(false)
  const [detailError, setDetailError] = useState<string | null>(null)

  const [selectedOrder, setSelectedOrder] = useState<OrderDetail | null>(null)
  const [orderDetailLoading, setOrderDetailLoading] = useState(false)
  const [orderDetailError, setOrderDetailError] = useState<string | null>(null)

  const { sortParam, cycleSort, getSort, getSortIndex } = useMultiSort(
    [{ field: 'totalRevenue', direction: 'desc' }],
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
    let active = true

    async function loadEmployees() {
      try {
        setLoading(true)
        setError(null)

        const response = await getEmployees({
          search,
          role,
          status,
          page,
          perPage,
          sort: sortParam,
        })

        if (!active) return
        setData(response)
      } catch {
        if (!active) return
        setError('Failed to load employees.')
      } finally {
        if (active) setLoading(false)
      }
    }

    loadEmployees()

    return () => {
      active = false
    }
  }, [search, role, status, page, perPage, sortParam])

  async function openEmployeeById(id: number) {
    try {
      setDetailLoading(true)
      setDetailError(null)
      setSelectedEmployee(null)
      setSelectedOrder(null)

      const response = await getEmployeeDetail(id)
      setSelectedEmployee(response)
    } catch {
      setDetailError('Failed to load employee details.')
    } finally {
      setDetailLoading(false)
    }
  }

  function openEmployee(row: EmployeeRow) {
    void openEmployeeById(row.id)
  }

  function openLookupEmployee(result: LookupResult) {
    if (result.type === 'employee') {
      void openEmployeeById(result.id)
    }
  }

  async function openSalesOrderById(id: number) {
    try {
      setOrderDetailLoading(true)
      setOrderDetailError(null)
      setSelectedOrder(null)
      setSelectedEmployee(null)
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
    setRole('all')
    setStatus('all')
    setPerPage(10)
    setPage(1)
  }

  const filterOptions = useMemo(
    () =>
      data?.filterOptions ?? {
        roles: [],
        statuses: ['active', 'inactive'],
      },
    [data],
  )

  const activeFilterCount =
    (role !== 'all' ? 1 : 0) +
    (status !== 'all' ? 1 : 0)

  const appliedFilterChips = useMemo(() => {
    const chips: string[] = []

    if (role !== 'all') {
      chips.push(`Role: ${formatEnumLabel(role)}`)
    }

    if (status !== 'all') {
      chips.push(`Status: ${formatEnumLabel(status)}`)
    }

    return chips
  }, [role, status])

  const inputClass = [
    'h-10 rounded-xl border px-3 text-sm outline-none transition',
    isDark
      ? 'border-slate-800 bg-slate-950 text-slate-200 placeholder:text-slate-600 focus:border-blue-500'
      : 'border-slate-200 bg-white text-slate-700 placeholder:text-slate-400 focus:border-blue-500',
  ].join(' ')

  return (
    <div className="space-y-6">
      <div>
        <h1 className={`text-3xl font-semibold tracking-tight ${isDark ? 'text-white' : 'text-slate-950'}`}>
          Employees
        </h1>
        <p className={`mt-1 text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
          Employee directory, sales accountability, commission metrics, and recent activity.
        </p>
      </div>

      <DedicatedEntitySearch
        entity="employees"
        placeholder="Find employee by name, number, email, phone, or role..."
        onOpenResult={openLookupEmployee}
        variant={theme}
        helperText="Searches all employees globally and opens the employee detail drawer."
      />

      <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
        <StatCard
          title="Employees"
          value={formatNumber(data?.summary.totalEmployees ?? 0)}
          helperText={`${formatNumber(data?.summary.activeEmployees ?? 0)} active employees`}
          compact
          variant={theme}
          icon={<UsersRound size={18} />}
        />
        <StatCard
          title="Sales Reps"
          value={formatNumber(data?.summary.salesRepresentatives ?? 0)}
          helperText="Employees in sales rep role"
          compact
          variant={theme}
        />
        <StatCard
          title="Revenue Handled"
          value={formatCurrency(data?.summary.totalRevenue ?? 0)}
          helperText="Attributed line-item revenue"
          compact
          variant={theme}
        />
        <StatCard
          title="Profit Handled"
          value={formatCurrency(data?.summary.totalProfit ?? 0)}
          helperText="Attributed line-item profit"
          compact
          variant={theme}
        />
        <StatCard
          title="Pending Commission"
          value={formatCurrency(data?.summary.pendingCommission ?? 0)}
          helperText="Unpaid commission payouts"
          compact
          variant={theme}
          icon={<BadgeDollarSign size={18} />}
        />
      </div>

      <SectionCard
        title="Employee List"
        description="Search, filter, sort, and open employee performance in the right-side detail drawer."
        variant={theme}
      >
        <FilterToolbar
          search={search}
          onSearchChange={(value) => {
            setSearch(value)
            setPage(1)
          }}
          searchPlaceholder="Search name, number, email, phone..."
          activeCount={activeFilterCount}
          onOpenFilters={() => setFiltersOpen(true)}
          onReset={resetFilters}
          variant={theme}
        >
          <AppliedFilterChips chips={appliedFilterChips} variant={theme} />
        </FilterToolbar>

        <EmployeesTable
          employees={data?.employees ?? []}
          loading={loading}
          error={error}
          variant={theme}
          onView={openEmployee}
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
              label="employees"
            />
          </div>
        ) : null}
      </SectionCard>

      <FilterDrawer
        open={filtersOpen}
        title="Employee Filters"
        activeCount={activeFilterCount}
        onClose={() => setFiltersOpen(false)}
        onReset={resetFilters}
        onApply={() => setFiltersOpen(false)}
        variant={theme}
      >
        <div className="grid grid-cols-1 gap-4">
          <FilterLabel label="Role" variant={theme}>
            <select
              value={role}
              onChange={(event) => {
                setRole(event.target.value)
                setPage(1)
              }}
              className={inputClass}
            >
              <option value="all">All roles</option>
              {filterOptions.roles.map((item) => (
                <option key={item} value={item}>
                  {formatEnumLabel(item)}
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

      <EmployeeDetailDrawer
        detail={selectedEmployee}
        loading={detailLoading}
        error={detailError}
        open={detailLoading || Boolean(detailError) || Boolean(selectedEmployee)}
        onClose={() => {
          setSelectedEmployee(null)
          setDetailError(null)
        }}
        onViewSalesOrder={(order) => {
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

function EmployeesTable({
  employees,
  loading,
  error,
  variant,
  onView,
  getSort,
  getSortIndex,
  onSort,
}: {
  employees: EmployeeRow[]
  loading: boolean
  error: string | null
  variant: 'light' | 'dark'
  onView: (employee: EmployeeRow) => void
  getSort: (field: string) => SortItem | undefined
  getSortIndex: (field: string) => number | undefined
  onSort: (field: string) => void
}) {
  const isDark = variant === 'dark'

  return (
    <div className="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[1240px] divide-y divide-slate-200 dark:divide-slate-800">
          <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
            <tr>
              <SortableHeader label="Employee" field="name" sort={getSort('name')} sortIndex={getSortIndex('name')} onSort={onSort} />
              <SortableHeader label="Number" field="employeeNumber" sort={getSort('employeeNumber')} sortIndex={getSortIndex('employeeNumber')} onSort={onSort} />
              <SortableHeader label="Role" field="role" sort={getSort('role')} sortIndex={getSortIndex('role')} onSort={onSort} />
              <SortableHeader label="Orders" field="salesOrderCount" sort={getSort('salesOrderCount')} sortIndex={getSortIndex('salesOrderCount')} onSort={onSort} align="right" />
              <SortableHeader label="Units" field="unitsSold" sort={getSort('unitsSold')} sortIndex={getSortIndex('unitsSold')} onSort={onSort} align="right" />
              <SortableHeader label="Revenue" field="totalRevenue" sort={getSort('totalRevenue')} sortIndex={getSortIndex('totalRevenue')} onSort={onSort} align="right" />
              <SortableHeader label="Profit" field="totalProfit" sort={getSort('totalProfit')} sortIndex={getSortIndex('totalProfit')} onSort={onSort} align="right" />
              <SortableHeader label="Commission" field="totalCommission" sort={getSort('totalCommission')} sortIndex={getSortIndex('totalCommission')} onSort={onSort} align="right" />
              <SortableHeader label="Last Sale" field="lastSaleAt" sort={getSort('lastSaleAt')} sortIndex={getSortIndex('lastSaleAt')} onSort={onSort} />
              <SortableHeader label="Status" field="status" sort={getSort('status')} sortIndex={getSortIndex('status')} onSort={onSort} />
              <th className="px-4 py-3" />
            </tr>
          </thead>

          <tbody className={`divide-y ${isDark ? 'divide-slate-800 bg-slate-900' : 'divide-slate-100 bg-white'}`}>
            {loading || error || employees.length === 0 ? (
              <TableEmptyState
                colSpan={11}
                loading={loading}
                error={error}
                emptyText="No employees found for the selected filters."
                variant={variant}
              />
            ) : (
              employees.map((employee) => (
                <tr key={employee.id} className={isDark ? 'hover:bg-slate-950/70' : 'hover:bg-slate-50'}>
                  <td className="px-4 py-4">
                    <p className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>{employee.displayName}</p>
                    <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>{employee.email}</p>
                  </td>
                  <td className={`px-4 py-4 font-data text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                    {employee.employeeNumber}
                  </td>
                  <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                    <p>{formatEnumLabel(employee.role)}</p>
                    <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>{formatPhoneNumber(employee.phone) || '-'}</p>
                  </td>
                  <td className={`px-4 py-4 text-right text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>{formatNumber(employee.salesOrderCount)}</td>
                  <td className={`px-4 py-4 text-right text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>{formatNumber(employee.unitsSold)}</td>
                  <td className={`px-4 py-4 text-right font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>{formatCurrency(employee.totalRevenue)}</td>
                  <td className={`px-4 py-4 text-right font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>{formatCurrency(employee.totalProfit)}</td>
                  <td className={`px-4 py-4 text-right text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>{formatCurrency(employee.totalCommission)}</td>
                  <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>{formatDate(employee.lastSaleAt)}</td>
                  <td className="px-4 py-4"><StatusBadge status={employee.status} variant={variant} /></td>
                  <td className="px-4 py-4 text-right">
                    <button
                      type="button"
                      onClick={() => onView(employee)}
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
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}

function EmployeeDetailDrawer({
  detail,
  loading,
  error,
  open,
  onClose,
  onViewSalesOrder,
  variant,
}: {
  detail: EmployeeDetailResponse | null
  loading: boolean
  error: string | null
  open: boolean
  onClose: () => void
  onViewSalesOrder: (order: EmployeeRecentSalesOrder) => void
  variant: 'light' | 'dark'
}) {
  return (
    <RightDrawer
      open={open}
      title={detail?.employee.displayName ?? 'Employee Details'}
      subtitle="Employee profile, sales activity, and commission performance"
      onClose={onClose}
      widthClassName="max-w-5xl"
    >
      {loading ? (
        <div className="rounded-2xl bg-slate-50 p-5 text-sm text-slate-500">Loading employee details...</div>
      ) : error ? (
        <div className="rounded-2xl bg-rose-50 p-5 text-sm text-rose-700">{error}</div>
      ) : detail ? (
        <EmployeeDetailContent detail={detail} variant={variant} onViewSalesOrder={onViewSalesOrder} />
      ) : null}
    </RightDrawer>
  )
}

function EmployeeDetailContent({
  detail,
  variant,
  onViewSalesOrder,
}: {
  detail: EmployeeDetailResponse
  variant: 'light' | 'dark'
  onViewSalesOrder: (order: EmployeeRecentSalesOrder) => void
}) {
  const isDark = variant === 'dark'
  const employee = detail.employee

  return (
    <div className="space-y-5">
      <div>
        <h2 className={`text-xl font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
          {employee.displayName}
        </h2>
        <div className={`mt-1 space-y-1 text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
          <p>{employee.email}</p>
          {employee.phone ? <p>{formatPhoneNumber(employee.phone)}</p> : null}
          <p>Employee #: {employee.employeeNumber}</p>
          <p>Role: {formatEnumLabel(employee.role)}</p>
        </div>
      </div>

      <div className="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <MetricBox label="Orders" value={formatNumber(detail.metrics.salesOrderCount)} variant={variant} />
        <MetricBox label="Units" value={formatNumber(detail.metrics.unitsSold)} variant={variant} />
        <MetricBox label="Revenue" value={formatCurrency(detail.metrics.totalRevenue)} variant={variant} />
        <MetricBox label="Profit" value={formatCurrency(detail.metrics.totalProfit)} variant={variant} />
        <MetricBox label="Commission" value={formatCurrency(detail.metrics.totalCommission)} variant={variant} />
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <DetailCard title="Profile" variant={variant}>
          <p>Status: {formatEnumLabel(employee.status)}</p>
          <p>Created: {formatDate(employee.createdAt)}</p>
          <p>Last sale: {formatDate(detail.metrics.lastSaleAt)}</p>
        </DetailCard>
        <DetailCard title="Commission" variant={variant}>
          <p>Pending: {formatCurrency(detail.metrics.pendingCommission)}</p>
          <p>Paid: {formatCurrency(detail.metrics.paidCommission)}</p>
          <p>Last paid: {formatDate(detail.metrics.lastPaidAt)}</p>
        </DetailCard>
        <DetailCard title="Averages" variant={variant}>
          <p>Average line value: {formatCurrency(detail.metrics.averageLineValue)}</p>
          <p>
            Revenue per order:{' '}
            {detail.metrics.salesOrderCount > 0
              ? formatCurrency(Number(detail.metrics.totalRevenue) / detail.metrics.salesOrderCount)
              : formatCurrency(0)}
          </p>
          <p>
            Profit per order:{' '}
            {detail.metrics.salesOrderCount > 0
              ? formatCurrency(Number(detail.metrics.totalProfit) / detail.metrics.salesOrderCount)
              : formatCurrency(0)}
          </p>
        </DetailCard>
      </div>

      <DetailTable title="Recent Sales Orders" headings={['SO', 'Customer', 'Date', 'Channel', 'Status', 'Qty', 'Revenue', 'Profit', 'Commission', '']} variant={variant}>
        {detail.recentSalesOrders.length ? (
          detail.recentSalesOrders.map((order) => (
            <tr key={order.id}>
              <td className="px-3 py-3 font-semibold">{order.orderNumber}</td>
              <td className="px-3 py-3">{order.customerName || '-'}</td>
              <td className="px-3 py-3">{formatDate(order.orderedAt)}</td>
              <td className="px-3 py-3">{formatEnumLabel(order.channel)}</td>
              <td className="px-3 py-3"><WorkflowStatusBadge status={order.status} variant={variant} /></td>
              <td className="px-3 py-3">{formatNumber(order.quantity)}</td>
              <td className="px-3 py-3 font-semibold">{formatCurrency(order.totalValue)}</td>
              <td className="px-3 py-3 font-semibold">{formatCurrency(order.profit)}</td>
              <td className="px-3 py-3">{formatCurrency(order.commission)}</td>
              <td className="px-3 py-3 text-right">
                <button
                  type="button"
                  onClick={() => onViewSalesOrder(order)}
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
          <tr><td colSpan={10} className="px-3 py-6 text-center text-slate-500">No sales orders found.</td></tr>
        )}
      </DetailTable>

      <DetailTable title="Top Products" headings={['Product', 'Qty', 'Revenue', 'Profit', 'Commission']} variant={variant}>
        {detail.topProducts.length ? (
          detail.topProducts.map((product) => (
            <tr key={product.id}>
              <td className="px-3 py-3">
                <p className={`font-medium ${isDark ? 'text-white' : 'text-slate-950'}`}>{product.title}</p>
                <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>{[product.brandName, product.sku, product.modelNumber].filter(Boolean).join(' / ')}</p>
              </td>
              <td className="px-3 py-3">{formatNumber(product.quantity)}</td>
              <td className="px-3 py-3 font-semibold">{formatCurrency(product.totalValue)}</td>
              <td className="px-3 py-3 font-semibold">{formatCurrency(product.profit)}</td>
              <td className="px-3 py-3">{formatCurrency(product.commission)}</td>
            </tr>
          ))
        ) : (
          <tr><td colSpan={5} className="px-3 py-6 text-center text-slate-500">No product data found.</td></tr>
        )}
      </DetailTable>

      <DetailTable title="Commission Payouts" headings={['Period', 'Commission', 'Status', 'Paid At']} variant={variant}>
        {detail.commissionPayouts.length ? (
          detail.commissionPayouts.map((payout) => (
            <tr key={payout.id}>
              <td className="px-3 py-3">{formatDate(payout.periodStart)} - {formatDate(payout.periodEnd)}</td>
              <td className="px-3 py-3 font-semibold">{formatCurrency(payout.totalCommission)}</td>
              <td className="px-3 py-3"><WorkflowStatusBadge status={payout.status} variant={variant} /></td>
              <td className="px-3 py-3">{formatDate(payout.paidAt)}</td>
            </tr>
          ))
        ) : (
          <tr><td colSpan={4} className="px-3 py-6 text-center text-slate-500">No commission payouts found.</td></tr>
        )}
      </DetailTable>
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
      subtitle="Sales order detail opened from employee activity"
      onClose={onClose}
      widthClassName="max-w-5xl"
    >
      {loading ? (
        <div className="rounded-2xl bg-slate-50 p-5 text-sm text-slate-500">Loading sales order details...</div>
      ) : error ? (
        <div className="rounded-2xl bg-rose-50 p-5 text-sm text-rose-700">{error}</div>
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
          <p className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>{order.counterpartyName}</p>
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
          <p>Completed: {formatDate(order.completedAt)}</p>
        </DetailCard>
      </div>

      <div className="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <MetricBox label="Subtotal" value={formatCurrency(order.totals.subtotal)} variant={variant} />
        <MetricBox label="Discount" value={formatCurrency(order.totals.discountAmount)} variant={variant} />
        <MetricBox label="Tax" value={formatCurrency(order.totals.taxAmount)} variant={variant} />
        <MetricBox label="Shipping / Other" value={formatCurrency(Number(order.totals.shippingAmount) + Number(order.totals.otherAmount))} variant={variant} />
        <MetricBox label="Grand Total" value={formatCurrency(order.totals.grandTotal)} variant={variant} />
      </div>

      <DetailTable title="Line Items" headings={['Product', 'Qty', 'Unit Price', 'Cost', 'Total', 'Profit', 'Commission']} variant={variant}>
        {items.map((item) => (
          <tr key={item.id}>
            <td className="px-3 py-3">
              <p className={`font-medium ${isDark ? 'text-white' : 'text-slate-950'}`}>{item.productTitle}</p>
              <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>{[item.brandName, item.sku, item.modelNumber].filter(Boolean).join(' / ')}</p>
            </td>
            <td className="px-3 py-3">{formatNumber(item.quantity)}</td>
            <td className="px-3 py-3">{formatCurrency(item.unitPrice)}</td>
            <td className="px-3 py-3">{formatCurrency(item.costBasis)}</td>
            <td className="px-3 py-3 font-semibold">{formatCurrency(item.lineTotal)}</td>
            <td className="px-3 py-3 font-semibold">{formatCurrency(item.lineProfit)}</td>
            <td className="px-3 py-3">{formatCurrency(item.commissionTotal)}</td>
          </tr>
        ))}
      </DetailTable>
    </div>
  )
}

function DetailTable({
  title,
  headings,
  children,
  variant,
}: {
  title: string
  headings: string[]
  children: ReactNode
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <div>
      <h3 className={`mb-3 text-base font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>{title}</h3>
      <div className="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
            <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
              <tr>
                {headings.map((heading) => (
                  <th key={heading} className="whitespace-nowrap px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    {heading}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className={`divide-y text-sm ${isDark ? 'divide-slate-800 bg-slate-900 text-slate-300' : 'divide-slate-100 bg-white text-slate-700'}`}>
              {children}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}