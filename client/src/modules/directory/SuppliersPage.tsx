import { useEffect, useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import { Building2, Eye, PackageCheck, Truck } from 'lucide-react'
import { useOutletContext } from 'react-router-dom'
import SectionCard from '../../shared/components/SectionCard'
import StatCard from '../../shared/components/StatCard'
import RightDrawer from '../../shared/components/RightDrawer'
import SortableHeader from '../../shared/components/SortableHeader'
import DedicatedEntitySearch from '../../shared/components/DedicatedEntitySearch'
import FilterDrawer from '../../shared/components/FilterDrawer'
import FilterToolbar from '../../shared/components/FilterToolbar'
import AppliedFilterChips from '../../shared/components/AppliedFilterChips'
import { getOrderDetail } from '../orders/orders.api'
import { getSupplierDetail, getSuppliers } from './directory.api'
import type { DashboardOutletContext } from '../../app/DashboardLayout'
import type { LookupResult } from '../../shared/types/lookup.types'
import type { OrderDetail, PurchaseOrderDetailItem } from '../orders/orders.types'
import type { SortItem } from '../../shared/types/sort.types'
import type {
  SupplierDetailResponse,
  SupplierRecentPurchaseOrder,
  SupplierRow,
  SuppliersResponse,
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

export default function SuppliersPage() {
  const { setHeaderRange, setHeaderDateRangeControl, theme } =
    useOutletContext<DashboardOutletContext>()

  const [search, setSearch] = useState('')
  const [selectedState, setSelectedState] = useState('all')
  const [status, setStatus] = useState('all')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(10)
  const [data, setData] = useState<SuppliersResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [filtersOpen, setFiltersOpen] = useState(false)

  const [selectedSupplier, setSelectedSupplier] =
    useState<SupplierDetailResponse | null>(null)
  const [detailLoading, setDetailLoading] = useState(false)
  const [detailError, setDetailError] = useState<string | null>(null)

  const [selectedOrder, setSelectedOrder] = useState<OrderDetail | null>(null)
  const [orderDetailLoading, setOrderDetailLoading] = useState(false)
  const [orderDetailError, setOrderDetailError] = useState<string | null>(null)

  const { sortParam, cycleSort, getSort, getSortIndex } = useMultiSort(
    [{ field: 'totalSpend', direction: 'desc' }],
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

    async function loadSuppliers() {
      try {
        setLoading(true)
        setError(null)

        const response = await getSuppliers({
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
        setError('Failed to load suppliers.')
      } finally {
        if (active) setLoading(false)
      }
    }

    loadSuppliers()

    return () => {
      active = false
    }
  }, [search, selectedState, status, page, perPage, sortParam])

  async function openSupplierById(id: number) {
    try {
      setDetailLoading(true)
      setDetailError(null)
      setSelectedSupplier(null)
      setSelectedOrder(null)

      const response = await getSupplierDetail(id)
      setSelectedSupplier(response)
    } catch {
      setDetailError('Failed to load supplier details.')
    } finally {
      setDetailLoading(false)
    }
  }

  function openSupplier(row: SupplierRow) {
    void openSupplierById(row.id)
  }

  function openLookupSupplier(result: LookupResult) {
    if (result.type === 'supplier') {
      void openSupplierById(result.id)
    }
  }

  async function openPurchaseOrderById(id: number) {
    try {
      setOrderDetailLoading(true)
      setOrderDetailError(null)
      setSelectedOrder(null)
      setSelectedSupplier(null)
      setDetailError(null)

      const response = await getOrderDetail('purchase', id)
      setSelectedOrder(response)
    } catch {
      setOrderDetailError('Failed to load purchase order details.')
    } finally {
      setOrderDetailLoading(false)
    }
  }

  function resetFilters() {
    setSearch('')
    setSelectedState('all')
    setStatus('all')
    setPerPage(10)
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

  const selectedStateLabel = useMemo(() => {
    if (selectedState === 'all') return null

    return (
      filterOptions.states.find((state) => state.code === selectedState)?.name ??
      selectedState
    )
  }, [filterOptions.states, selectedState])

  const activeFilterCount =
    (selectedState !== 'all' ? 1 : 0) +
    (status !== 'all' ? 1 : 0)

  const appliedFilterChips = useMemo(() => {
    const chips: string[] = []

    if (selectedState !== 'all') {
      chips.push(`State: ${selectedStateLabel ?? selectedState}`)
    }

    if (status !== 'all') {
      chips.push(`Status: ${formatEnumLabel(status)}`)
    }

    return chips
  }, [selectedState, selectedStateLabel, status])

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
          Suppliers
        </h1>
        <p className={`mt-1 text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
          Supplier directory, purchase order history, product sourcing, and procurement performance.
        </p>
      </div>

      <DedicatedEntitySearch
        entity="suppliers"
        placeholder="Find supplier by name, contact, email, phone, website, city..."
        onOpenResult={openLookupSupplier}
        variant={theme}
        helperText="Searches all suppliers globally and opens the supplier detail drawer."
      />

      <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
        <StatCard
          title="Suppliers"
          value={formatNumber(data?.summary.totalSuppliers ?? 0)}
          helperText={`${formatNumber(data?.summary.activeSuppliers ?? 0)} active suppliers`}
          compact
          variant={theme}
          icon={<Building2 size={18} />}
        />
        <StatCard
          title="POs"
          value={formatNumber(data?.summary.purchaseOrderCount ?? 0)}
          helperText="All purchase orders"
          compact
          variant={theme}
          icon={<Truck size={18} />}
        />
        <StatCard
          title="Open POs"
          value={formatNumber(data?.summary.openPurchaseOrders ?? 0)}
          helperText="Issued or in transit"
          compact
          variant={theme}
        />
        <StatCard
          title="Total Spend"
          value={formatCurrency(data?.summary.totalSpend ?? 0)}
          helperText="Non-cancelled PO value"
          compact
          variant={theme}
        />
        <StatCard
          title="Products Sourced"
          value={formatNumber(data?.summary.productCount ?? 0)}
          helperText="Distinct supplier products"
          compact
          variant={theme}
          icon={<PackageCheck size={18} />}
        />
      </div>

      <SectionCard
        title="Supplier List"
        description="Search, filter, sort, and open suppliers in the right-side detail drawer."
        variant={theme}
      >
        <FilterToolbar
          search={search}
          onSearchChange={(value) => {
            setSearch(value)
            setPage(1)
          }}
          searchPlaceholder="Search supplier, contact, email, phone..."
          activeCount={activeFilterCount}
          onOpenFilters={() => setFiltersOpen(true)}
          onReset={resetFilters}
          variant={theme}
        >
          <AppliedFilterChips chips={appliedFilterChips} variant={theme} />
        </FilterToolbar>

        <SuppliersTable
          suppliers={data?.suppliers ?? []}
          loading={loading}
          error={error}
          variant={theme}
          onView={openSupplier}
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
              label="suppliers"
            />
          </div>
        ) : null}
      </SectionCard>

      <FilterDrawer
        open={filtersOpen}
        title="Supplier Filters"
        activeCount={activeFilterCount}
        onClose={() => setFiltersOpen(false)}
        onReset={resetFilters}
        onApply={() => setFiltersOpen(false)}
        variant={theme}
      >
        <div className="grid grid-cols-1 gap-4">
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

      <SupplierDetailDrawer
        detail={selectedSupplier}
        loading={detailLoading}
        error={detailError}
        open={detailLoading || Boolean(detailError) || Boolean(selectedSupplier)}
        onClose={() => {
          setSelectedSupplier(null)
          setDetailError(null)
        }}
        onViewPurchaseOrder={(order) => {
          void openPurchaseOrderById(order.id)
        }}
        variant={theme}
      />

      <PurchaseOrderDrawer
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

function SuppliersTable({
  suppliers,
  loading,
  error,
  variant,
  onView,
  getSort,
  getSortIndex,
  onSort,
}: {
  suppliers: SupplierRow[]
  loading: boolean
  error: string | null
  variant: 'light' | 'dark'
  onView: (supplier: SupplierRow) => void
  getSort: (field: string) => SortItem | undefined
  getSortIndex: (field: string) => number | undefined
  onSort: (field: string) => void
}) {
  const isDark = variant === 'dark'

  return (
    <div className="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[1280px] divide-y divide-slate-200 dark:divide-slate-800">
          <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
            <tr>
              <SortableHeader label="Supplier" field="name" sort={getSort('name')} sortIndex={getSortIndex('name')} onSort={onSort} />
              <SortableHeader label="Contact" field="contactPerson" sort={getSort('contactPerson')} sortIndex={getSortIndex('contactPerson')} onSort={onSort} />
              <SortableHeader label="Location" field="location" sort={getSort('location')} sortIndex={getSortIndex('location')} onSort={onSort} />
              <SortableHeader label="POs" field="purchaseOrderCount" sort={getSort('purchaseOrderCount')} sortIndex={getSortIndex('purchaseOrderCount')} onSort={onSort} align="right" />
              <SortableHeader label="Open" field="openPurchaseOrders" sort={getSort('openPurchaseOrders')} sortIndex={getSortIndex('openPurchaseOrders')} onSort={onSort} align="right" />
              <SortableHeader label="Spend" field="totalSpend" sort={getSort('totalSpend')} sortIndex={getSortIndex('totalSpend')} onSort={onSort} align="right" />
              <SortableHeader label="Products" field="productCount" sort={getSort('productCount')} sortIndex={getSortIndex('productCount')} onSort={onSort} align="right" />
              <SortableHeader label="Lead Time" field="averageLeadTimeDays" sort={getSort('averageLeadTimeDays')} sortIndex={getSortIndex('averageLeadTimeDays')} onSort={onSort} align="right" />
              <SortableHeader label="Last PO" field="lastOrderAt" sort={getSort('lastOrderAt')} sortIndex={getSortIndex('lastOrderAt')} onSort={onSort} />
              <SortableHeader label="Status" field="status" sort={getSort('status')} sortIndex={getSortIndex('status')} onSort={onSort} />
              <th className="px-4 py-3" />
            </tr>
          </thead>

          <tbody className={`divide-y ${isDark ? 'divide-slate-800 bg-slate-900' : 'divide-slate-100 bg-white'}`}>
            {loading || error || suppliers.length === 0 ? (
              <TableEmptyState
                colSpan={11}
                loading={loading}
                error={error}
                emptyText="No suppliers found for the selected filters."
                variant={variant}
              />
            ) : (
              suppliers.map((supplier) => (
                <tr key={supplier.id} className={isDark ? 'hover:bg-slate-950/70' : 'hover:bg-slate-50'}>
                  <td className="px-4 py-4">
                    <p className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>{supplier.name}</p>
                    <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>{supplier.website || supplier.email || '-'}</p>
                  </td>
                  <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                    <p className="font-medium">{supplier.contactPerson || '-'}</p>
                    <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>{formatPhoneNumber(supplier.phone) || supplier.email || '-'}</p>
                  </td>
                  <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                    {[supplier.city, supplier.state, supplier.country].filter(Boolean).join(', ') || '-'}
                  </td>
                  <td className={`px-4 py-4 text-right text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>{formatNumber(supplier.purchaseOrderCount)}</td>
                  <td className={`px-4 py-4 text-right text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>{formatNumber(supplier.openPurchaseOrders)}</td>
                  <td className={`px-4 py-4 text-right font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>{formatCurrency(supplier.totalSpend)}</td>
                  <td className={`px-4 py-4 text-right text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>{formatNumber(supplier.productCount)}</td>
                  <td className={`px-4 py-4 text-right text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>{Number(supplier.averageLeadTimeDays || 0).toFixed(1)} days</td>
                  <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>{formatDate(supplier.lastOrderAt)}</td>
                  <td className="px-4 py-4"><StatusBadge status={supplier.status} variant={variant} /></td>
                  <td className="px-4 py-4 text-right">
                    <button
                      type="button"
                      onClick={() => onView(supplier)}
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

function SupplierDetailDrawer({
  detail,
  loading,
  error,
  open,
  onClose,
  onViewPurchaseOrder,
  variant,
}: {
  detail: SupplierDetailResponse | null
  loading: boolean
  error: string | null
  open: boolean
  onClose: () => void
  onViewPurchaseOrder: (order: SupplierRecentPurchaseOrder) => void
  variant: 'light' | 'dark'
}) {
  return (
    <RightDrawer
      open={open}
      title={detail?.supplier.name ?? 'Supplier Details'}
      subtitle="Supplier profile, purchase history, and sourced products"
      onClose={onClose}
      widthClassName="max-w-5xl"
    >
      {loading ? (
        <div className="rounded-2xl bg-slate-50 p-5 text-sm text-slate-500">Loading supplier details...</div>
      ) : error ? (
        <div className="rounded-2xl bg-rose-50 p-5 text-sm text-rose-700">{error}</div>
      ) : detail ? (
        <SupplierDetailContent detail={detail} variant={variant} onViewPurchaseOrder={onViewPurchaseOrder} />
      ) : null}
    </RightDrawer>
  )
}

function SupplierDetailContent({
  detail,
  variant,
  onViewPurchaseOrder,
}: {
  detail: SupplierDetailResponse
  variant: 'light' | 'dark'
  onViewPurchaseOrder: (order: SupplierRecentPurchaseOrder) => void
}) {
  const isDark = variant === 'dark'
  const supplier = detail.supplier

  const address = [
    supplier.addressLine1,
    supplier.addressLine2,
    supplier.city,
    supplier.state,
    supplier.postalCode,
    supplier.country,
  ]
    .filter(Boolean)
    .join(', ')

  return (
    <div className="space-y-5">
      <div>
        <h2 className={`text-xl font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>{supplier.name}</h2>
        <div className={`mt-1 space-y-1 text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
          {supplier.contactPerson ? <p>Contact: {supplier.contactPerson}</p> : null}
          {supplier.email ? <p>{supplier.email}</p> : null}
          {supplier.phone ? <p>{formatPhoneNumber(supplier.phone)}</p> : null}
          {supplier.website ? <p>{supplier.website}</p> : null}
        </div>
      </div>

      <div className="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <MetricBox label="POs" value={formatNumber(detail.metrics.purchaseOrderCount)} variant={variant} />
        <MetricBox label="Open POs" value={formatNumber(detail.metrics.openPurchaseOrders)} variant={variant} />
        <MetricBox label="Total Spend" value={formatCurrency(detail.metrics.totalSpend)} variant={variant} />
        <MetricBox label="Products" value={formatNumber(detail.metrics.productCount)} variant={variant} />
        <MetricBox label="Lead Time" value={`${Number(detail.metrics.averageLeadTimeDays || 0).toFixed(1)} days`} variant={variant} />
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <DetailCard title="Address" variant={variant}>{address || 'No address available.'}</DetailCard>
        <DetailCard title="Procurement" variant={variant}>
          <p>Average PO: {formatCurrency(detail.metrics.averageOrderValue)}</p>
          <p>Fulfilled POs: {formatNumber(detail.metrics.fulfilledPurchaseOrders)}</p>
          <p>Last PO: {formatDate(detail.metrics.lastOrderAt)}</p>
        </DetailCard>
        <DetailCard title="Products" variant={variant}>
          <p>Preferred products: {formatNumber(detail.metrics.preferredProductCount)}</p>
          <p>Last received: {formatDate(detail.metrics.lastReceivedAt)}</p>
          <p>Status: {formatEnumLabel(supplier.status)}</p>
        </DetailCard>
      </div>

      <DetailTable title="Recent Purchase Orders" headings={['PO', 'Date', 'Expected', 'Received', 'Status', 'Items', 'Total', '']} variant={variant}>
        {detail.recentPurchaseOrders.length ? (
          detail.recentPurchaseOrders.map((order) => (
            <tr key={order.id}>
              <td className="px-3 py-3 font-semibold">{order.orderNumber}</td>
              <td className="px-3 py-3">{formatDate(order.orderedAt)}</td>
              <td className="px-3 py-3">{formatDate(order.expectedAt)}</td>
              <td className="px-3 py-3">{formatDate(order.receivedAt)}</td>
              <td className="px-3 py-3"><WorkflowStatusBadge status={order.status} variant={variant} /></td>
              <td className="px-3 py-3">{formatNumber(order.itemCount)} lines / {formatNumber(order.totalQuantity)} units</td>
              <td className="px-3 py-3 font-semibold">{formatCurrency(order.totalCost)}</td>
              <td className="px-3 py-3 text-right">
                <button
                  type="button"
                  onClick={() => onViewPurchaseOrder(order)}
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
          <tr><td colSpan={8} className="px-3 py-6 text-center text-slate-500">No purchase orders found.</td></tr>
        )}
      </DetailTable>

      <DetailTable title="Products Supplied" headings={['Product', 'Supplier SKU', 'Cost', 'Lead Time', 'Min Qty', 'Preferred', 'Status']} variant={variant}>
        {detail.products.length ? (
          detail.products.map((product) => (
            <tr key={`${product.id}-${product.supplierSku ?? ''}`}>
              <td className="px-3 py-3">
                <p className={`font-medium ${isDark ? 'text-white' : 'text-slate-950'}`}>{product.title}</p>
                <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>{[product.brandName, product.sku, product.modelNumber].filter(Boolean).join(' / ')}</p>
              </td>
              <td className="px-3 py-3 font-data">{product.supplierSku || '-'}</td>
              <td className="px-3 py-3">{formatCurrency(product.lastCost)}</td>
              <td className="px-3 py-3">{product.leadTimeDays !== null && product.leadTimeDays !== undefined ? `${product.leadTimeDays} days` : '-'}</td>
              <td className="px-3 py-3">{formatNumber(product.minOrderQty)}</td>
              <td className="px-3 py-3">{product.preferredSupplier ? 'Yes' : 'No'}</td>
              <td className="px-3 py-3"><StatusBadge status={product.status} variant={variant} /></td>
            </tr>
          ))
        ) : (
          <tr><td colSpan={7} className="px-3 py-6 text-center text-slate-500">No products found.</td></tr>
        )}
      </DetailTable>
    </div>
  )
}

function PurchaseOrderDrawer({
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
      title={order?.orderNumber ?? 'Purchase Order Details'}
      subtitle="Purchase order detail opened from supplier history"
      onClose={onClose}
      widthClassName="max-w-5xl"
    >
      {loading ? (
        <div className="rounded-2xl bg-slate-50 p-5 text-sm text-slate-500">Loading purchase order details...</div>
      ) : error ? (
        <div className="rounded-2xl bg-rose-50 p-5 text-sm text-rose-700">{error}</div>
      ) : order ? (
        <PurchaseOrderDetailContent order={order} variant={variant} />
      ) : null}
    </RightDrawer>
  )
}

function PurchaseOrderDetailContent({
  order,
  variant,
}: {
  order: OrderDetail
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'
  const items = order.items as PurchaseOrderDetailItem[]

  return (
    <div className="space-y-5">
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <DetailCard title="Supplier" variant={variant}>
          <p className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>{order.counterpartyName}</p>
          {order.counterpartyEmail ? <p>{order.counterpartyEmail}</p> : null}
          {order.counterpartyPhone ? <p>{formatPhoneNumber(order.counterpartyPhone)}</p> : null}
        </DetailCard>
        <DetailCard title="Status" variant={variant}><WorkflowStatusBadge status={order.status} variant={variant} /></DetailCard>
        <DetailCard title="Dates" variant={variant}>
          <p>Ordered: {formatDate(order.orderedAt)}</p>
          <p>Expected: {formatDate(order.expectedAt)}</p>
          <p>Received: {formatDate(order.receivedAt)}</p>
        </DetailCard>
      </div>

      <div className="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <MetricBox label="Subtotal" value={formatCurrency(order.totals.subtotal)} variant={variant} />
        <MetricBox label="Tax" value={formatCurrency(order.totals.taxAmount)} variant={variant} />
        <MetricBox label="Shipping" value={formatCurrency(order.totals.shippingAmount)} variant={variant} />
        <MetricBox label="Other" value={formatCurrency(order.totals.otherAmount)} variant={variant} />
        <MetricBox label="Grand Total" value={formatCurrency(order.totals.grandTotal)} variant={variant} />
      </div>

      <DetailTable title="Line Items" headings={['Product', 'Qty', 'Unit Cost', 'Line Total']} variant={variant}>
        {items.map((item) => (
          <tr key={item.id}>
            <td className="px-3 py-3">
              <p className={`font-medium ${isDark ? 'text-white' : 'text-slate-950'}`}>{item.productTitle}</p>
              <p className={`text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>{[item.brandName, item.sku, item.modelNumber].filter(Boolean).join(' / ')}</p>
            </td>
            <td className="px-3 py-3">{formatNumber(item.quantity)}</td>
            <td className="px-3 py-3">{formatCurrency(item.unitCost)}</td>
            <td className="px-3 py-3 font-semibold">{formatCurrency(item.lineTotal)}</td>
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