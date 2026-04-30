import { useCallback, useEffect, useMemo, useState } from 'react'
import { X } from 'lucide-react'
import { useOutletContext } from 'react-router-dom'
import SectionCard from '../../components/dashboard/SectionCard'
import StatCard from '../../components/dashboard/StatCard'
import { getInventoryAlerts, getInventoryProductDetail } from '../../api/inventory'
import type { DashboardOutletContext } from '../../layouts/DashboardLayout'
import type {
  InventoryAlertsResponse,
  InventoryProductDetail,
  InventoryProductRow,
} from '../../types/inventory'
import { formatCurrency, formatNumber } from '../../utils/format'
import {
  CheckboxGroup,
  DEFAULT_INVENTORY_DATE_RANGE,
  FilterLabel,
  INVENTORY_MAX_DATE,
  PaginationControls,
  ProductTable,
  formatDate,
  formatDateRangeLabel,
  formatEnumLabel,
  normalizeDateRange,
} from './inventoryShared'

export default function InventoryAlertsPage() {
  const { setHeaderRange, setHeaderDateRangeControl, theme } =
    useOutletContext<DashboardOutletContext>()

  const [dateRange, setDateRange] = useState(DEFAULT_INVENTORY_DATE_RANGE)
  const [search, setSearch] = useState('')
  const [alertTypes, setAlertTypes] = useState<string[]>([])
  const [category, setCategory] = useState('all')
  const [brand, setBrand] = useState('all')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(10)

  const [data, setData] = useState<InventoryAlertsResponse | null>(null)
  const [loading, setLoading] = useState(true)

  const [selectedProduct, setSelectedProduct] =
    useState<InventoryProductDetail | null>(null)
  const [detailLoading, setDetailLoading] = useState(false)

  const isDark = theme === 'dark'

  const updateDateRange = useCallback(
    (nextRange: { startDate: string; endDate: string }) => {
      setDateRange(normalizeDateRange(nextRange))
      setPage(1)
    },
    []
  )

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
      maxDate: INVENTORY_MAX_DATE,
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
    let active = true

    async function loadAlerts() {
      setLoading(true)

      const response = await getInventoryAlerts({
        startDate: dateRange.startDate,
        endDate: dateRange.endDate,
        search,
        alertTypes,
        category,
        brand,
        page,
        perPage,
      })

      if (!active) return

      setData(response)
      setLoading(false)
    }

    loadAlerts()

    return () => {
      active = false
    }
  }, [
    dateRange.startDate,
    dateRange.endDate,
    search,
    alertTypes,
    category,
    brand,
    page,
    perPage,
  ])

  async function openProduct(row: InventoryProductRow) {
    setDetailLoading(true)

    try {
      const response = await getInventoryProductDetail(row.id)
      setSelectedProduct(response)
    } finally {
      setDetailLoading(false)
    }
  }

  const filterOptions = useMemo(
    () =>
      data?.filterOptions ?? {
        categories: [],
        brands: [],
        statuses: [],
        serializedOptions: [],
        alertTypes: [],
      },
    [data]
  )

  const inputClass = [
    'h-10 rounded-xl border px-3 text-sm outline-none transition',
    isDark
      ? 'border-slate-800 bg-slate-950 text-slate-200 placeholder:text-slate-600 focus:border-blue-500'
      : 'border-slate-200 bg-white text-slate-700 placeholder:text-slate-400 focus:border-blue-500',
  ].join(' ')

  return (
    <div className="space-y-8">
      <div className="grid gap-4 md:grid-cols-3">
        <StatCard
          title="Out of Stock"
          value={data ? formatNumber(data.summary.outOfStock) : '-'}
          helperText="0 units available"
          variant={theme}
          compact
        />

        <StatCard
          title="Low Stock"
          value={data ? formatNumber(data.summary.lowStock) : '-'}
          helperText="1-5 units available"
          variant={theme}
          compact
        />

        <StatCard
          title="Stale Stock"
          value={data ? formatNumber(data.summary.staleStock) : '-'}
          helperText="No recent sale"
          variant={theme}
          compact
        />
      </div>

      <SectionCard
        title="Inventory Alerts"
        description="Products needing replenishment or review."
        variant={theme}
      >
        <div className="space-y-5">
          <div className="grid gap-3 xl:grid-cols-[minmax(220px,1fr)_180px_180px_140px_auto]">
            <FilterLabel label="Search" variant={theme}>
              <input
                value={search}
                onChange={(event) => {
                  setSearch(event.target.value)
                  setPage(1)
                }}
                placeholder="Product, SKU, model, brand..."
                className={inputClass}
              />
            </FilterLabel>

            <FilterLabel label="Category" variant={theme}>
              <select
                value={category}
                onChange={(event) => {
                  setCategory(event.target.value)
                  setPage(1)
                }}
                className={inputClass}
              >
                <option value="all">All categories</option>
                {filterOptions.categories.map((item) => (
                  <option key={item.id} value={item.id}>
                    {item.name}
                  </option>
                ))}
              </select>
            </FilterLabel>

            <FilterLabel label="Brand" variant={theme}>
              <select
                value={brand}
                onChange={(event) => {
                  setBrand(event.target.value)
                  setPage(1)
                }}
                className={inputClass}
              >
                <option value="all">All brands</option>
                {filterOptions.brands.map((item) => (
                  <option key={item.id} value={item.id}>
                    {item.name}
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
                  setAlertTypes([])
                  setCategory('all')
                  setBrand('all')
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

          <CheckboxGroup
            label="Alert Types"
            options={(filterOptions.alertTypes ?? []).map((item) => ({
              label: formatEnumLabel(item),
              value: item,
            }))}
            selected={alertTypes}
            onChange={(value) => {
              setAlertTypes(value)
              setPage(1)
            }}
            variant={theme}
          />

          {loading ? (
            <div
              className={`py-12 text-center text-sm ${
                isDark ? 'text-slate-400' : 'text-slate-500'
              }`}
            >
              Loading alerts...
            </div>
          ) : data ? (
            <>
              <ProductTable
                rows={data.rows}
                variant={theme}
                onView={openProduct}
              />

              <PaginationControls
                pagination={data.pagination}
                onPageChange={setPage}
                variant={theme}
              />
            </>
          ) : null}
        </div>
      </SectionCard>

      {detailLoading || selectedProduct ? (
        <ProductDrawer
          product={selectedProduct}
          loading={detailLoading}
          onClose={() => setSelectedProduct(null)}
          variant={theme}
        />
      ) : null}
    </div>
  )
}

function ProductDrawer({
  product,
  loading,
  onClose,
  variant,
}: {
  product: InventoryProductDetail | null
  loading: boolean
  onClose: () => void
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <div className="fixed inset-0 z-50 flex justify-end bg-slate-950/40 backdrop-blur-sm">
      <div
        className={[
          'h-full w-full max-w-[960px] overflow-y-auto border-l shadow-2xl',
          isDark
            ? 'border-slate-800 bg-slate-950 text-slate-100'
            : 'border-slate-200 bg-white text-slate-900',
        ].join(' ')}
      >
        <div
          className={[
            'sticky top-0 z-10 flex items-start justify-between border-b px-6 py-5',
            isDark
              ? 'border-slate-800 bg-slate-950'
              : 'border-slate-200 bg-white',
          ].join(' ')}
        >
          <div>
            <h2 className="text-lg font-semibold">
              {product?.title ?? 'Product Details'}
            </h2>
            <p
              className={`mt-1 text-sm ${
                isDark ? 'text-slate-400' : 'text-slate-500'
              }`}
            >
              Current stock, batches, movements, and serials.
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
          {loading || !product ? (
            <div
              className={`py-20 text-center text-sm ${
                isDark ? 'text-slate-400' : 'text-slate-500'
              }`}
            >
              Loading product details...
            </div>
          ) : (
            <div className="space-y-6">
              <div className="grid gap-4 md:grid-cols-4">
                <MiniBox
                  label="Stock"
                  value={`${formatNumber(product.stockQty)} units`}
                  variant={variant}
                />
                <MiniBox
                  label="Inventory Value"
                  value={formatCurrency(product.inventoryValue)}
                  variant={variant}
                />
                <MiniBox
                  label="Avg Cost"
                  value={formatCurrency(product.avgUnitCost)}
                  variant={variant}
                />
                <MiniBox
                  label="Last Sold"
                  value={formatDate(product.lastSoldAt)}
                  variant={variant}
                />
              </div>

              <div
                className={[
                  'rounded-2xl border p-4',
                  isDark
                    ? 'border-slate-800 bg-slate-900'
                    : 'border-slate-200 bg-slate-50',
                ].join(' ')}
              >
                <div className="font-semibold">{product.title}</div>
                <div
                  className={`mt-2 text-sm ${
                    isDark ? 'text-slate-400' : 'text-slate-500'
                  }`}
                >
                  {[
                    product.sku,
                    product.modelNumber,
                    product.brandName,
                    product.categoryName,
                    product.subCategoryName,
                  ]
                    .filter(Boolean)
                    .join(' / ')}
                </div>
              </div>

              <DetailTable
                title="Open Batches"
                variant={variant}
                headings={[
                  'Batch',
                  'Received',
                  'Received Qty',
                  'Remaining Qty',
                  'Unit Cost',
                  'Remaining Value',
                  'PO',
                ]}
              >
                {product.batches.map((row) => (
                  <tr key={row.id}>
                    <td className="px-4 py-3">{row.batchCode || '-'}</td>
                    <td className="px-4 py-3">{formatDate(row.receivedAt)}</td>
                    <td className="px-4 py-3 font-data">
                      {formatNumber(row.qtyReceived)}
                    </td>
                    <td className="px-4 py-3 font-data">
                      {formatNumber(row.qtyRemaining)}
                    </td>
                    <td className="px-4 py-3 font-data">
                      {formatCurrency(row.unitCost)}
                    </td>
                    <td className="px-4 py-3 font-data">
                      {formatCurrency(row.remainingValue)}
                    </td>
                    <td className="px-4 py-3">
                      {row.purchaseOrderNumber || '-'}
                    </td>
                  </tr>
                ))}
              </DetailTable>

              <DetailTable
                title="Recent Movements"
                variant={variant}
                headings={['Date', 'Type', 'Qty', 'Batch', 'Unit Cost', 'Reference']}
              >
                {product.movements.map((row) => (
                  <tr key={row.id}>
                    <td className="px-4 py-3">{formatDate(row.movedAt)}</td>
                    <td className="px-4 py-3">
                      {formatEnumLabel(row.movementType)}
                    </td>
                    <td
                      className={[
                        'px-4 py-3 font-data font-semibold',
                        row.qtyChange >= 0
                          ? 'text-emerald-600'
                          : 'text-rose-600',
                      ].join(' ')}
                    >
                      {row.qtyChange >= 0 ? '+' : ''}
                      {row.qtyChange}
                    </td>
                    <td className="px-4 py-3">{row.batchCode || '-'}</td>
                    <td className="px-4 py-3 font-data">
                      {row.unitCost ? formatCurrency(row.unitCost) : '-'}
                    </td>
                    <td className="px-4 py-3">
                      {[row.referenceType, row.referenceId]
                        .filter(Boolean)
                        .join(' #') || '-'}
                    </td>
                  </tr>
                ))}
              </DetailTable>

              {product.isSerialized ? (
                <DetailTable
                  title="Serial Numbers"
                  variant={variant}
                  headings={['Serial', 'Status', 'Batch', 'Received']}
                >
                  {product.serials.map((row) => (
                    <tr key={row.id}>
                      <td className="px-4 py-3 font-data">
                        {row.serialNumber}
                      </td>
                      <td className="px-4 py-3">
                        {formatEnumLabel(row.status)}
                      </td>
                      <td className="px-4 py-3">{row.batchCode || '-'}</td>
                      <td className="px-4 py-3">
                        {formatDate(row.receivedAt)}
                      </td>
                    </tr>
                  ))}
                </DetailTable>
              ) : null}
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

function MiniBox({
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
        className={`text-xs font-semibold uppercase tracking-[0.12em] ${
          isDark ? 'text-slate-500' : 'text-slate-400'
        }`}
      >
        {label}
      </div>
      <div
        className={`mt-2 font-data text-sm font-semibold ${
          isDark ? 'text-white' : 'text-slate-950'
        }`}
      >
        {value}
      </div>
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
  children: React.ReactNode
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <div>
      <h3
        className={`mb-3 text-sm font-semibold ${
          isDark ? 'text-white' : 'text-slate-950'
        }`}
      >
        {title}
      </h3>

      <div
        className={[
          'overflow-hidden rounded-2xl border',
          isDark ? 'border-slate-800' : 'border-slate-200',
        ].join(' ')}
      >
        <div className="overflow-x-auto">
          <table className="min-w-[860px] w-full border-collapse">
            <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
              <tr>
                {headings.map((heading) => (
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

            <tbody
              className={[
                'divide-y text-sm',
                isDark
                  ? 'divide-slate-800 bg-slate-900'
                  : 'divide-slate-100 bg-white',
              ].join(' ')}
            >
              {children}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}