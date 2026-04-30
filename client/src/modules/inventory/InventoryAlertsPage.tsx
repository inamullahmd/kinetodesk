import { useEffect, useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import { useOutletContext } from 'react-router-dom'
import SectionCard from '../../shared/components/SectionCard'
import StatCard from '../../shared/components/StatCard'
import RightDrawer from '../../shared/components/RightDrawer'
import FilterDrawer from '../../shared/components/FilterDrawer'
import FilterToolbar from '../../shared/components/FilterToolbar'
import AppliedFilterChips from '../../shared/components/AppliedFilterChips'
import { getInventoryAlerts, getInventoryProductDetail } from './inventory.api'
import { useMultiSort } from '../../shared/hooks/useMultiSort'
import type { DashboardOutletContext } from '../../app/DashboardLayout'
import type {
  InventoryAlertsResponse,
  InventoryProductDetail,
  InventoryProductRow,
} from './inventory.types'
import {
  formatCurrency, formatNumber,
  formatDate,
  formatEnumLabel,
} from '../../shared/utils/format'
import {
  CheckboxGroup,
  DEFAULT_INVENTORY_DATE_RANGE,
  FilterLabel,
  PaginationControls,
  ProductTable,
} from './inventoryShared'


function getOptionName<T extends { id: number | string; name: string }>(
  options: T[],
  value: string,
) {
  return options.find((item) => String(item.id) === String(value))?.name ?? value
}

export default function InventoryAlertsPage() {
  const { setHeaderRange, setHeaderDateRangeControl, theme } =
    useOutletContext<DashboardOutletContext>()

  const [search, setSearch] = useState('')
  const [alertTypes, setAlertTypes] = useState<string[]>([])
  const [category, setCategory] = useState('all')
  const [brand, setBrand] = useState('all')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(10)

  const [data, setData] = useState<InventoryAlertsResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [filtersOpen, setFiltersOpen] = useState(false)

  const [selectedProduct, setSelectedProduct] =
    useState<InventoryProductDetail | null>(null)
  const [detailLoading, setDetailLoading] = useState(false)
  const [detailError, setDetailError] = useState<string | null>(null)

  const { sortParam, cycleSort, getSort, getSortIndex } = useMultiSort(
    [
      { field: 'currentStock', direction: 'asc' },
      { field: 'product', direction: 'asc' },
    ],
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

    async function loadAlerts() {
      try {
        setLoading(true)
        setError(null)

        const response = await getInventoryAlerts({
          startDate: DEFAULT_INVENTORY_DATE_RANGE.startDate,
          endDate: DEFAULT_INVENTORY_DATE_RANGE.endDate,
          search,
          alertTypes,
          category,
          brand,
          page,
          perPage,
          sort: sortParam,
        })

        if (!active) return

        setData(response)
      } catch {
        if (!active) return
        setError('Failed to load inventory alerts.')
      } finally {
        if (active) setLoading(false)
      }
    }

    loadAlerts()

    return () => {
      active = false
    }
  }, [
    search,
    alertTypes,
    category,
    brand,
    page,
    perPage,
    sortParam,
  ])

  async function openProduct(row: InventoryProductRow) {
    try {
      setDetailLoading(true)
      setDetailError(null)
      setSelectedProduct(null)

      const response = await getInventoryProductDetail(row.id)
      setSelectedProduct(response)
    } catch {
      setDetailError('Failed to load product details.')
    } finally {
      setDetailLoading(false)
    }
  }

  function resetFilters() {
    setSearch('')
    setAlertTypes([])
    setCategory('all')
    setBrand('all')
    setPerPage(10)
    setPage(1)
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
    [data],
  )

  const activeFilterCount =
    alertTypes.length +
    (category !== 'all' ? 1 : 0) +
    (brand !== 'all' ? 1 : 0)

  const appliedFilterChips = useMemo(() => {
    const chips: string[] = []

    alertTypes.forEach((alertType) => {
      chips.push(`Alert: ${formatEnumLabel(alertType)}`)
    })

    if (category !== 'all') {
      chips.push(`Category: ${getOptionName(filterOptions.categories, category)}`)
    }

    if (brand !== 'all') {
      chips.push(`Brand: ${getOptionName(filterOptions.brands, brand)}`)
    }

    return chips
  }, [
    alertTypes,
    brand,
    category,
    filterOptions.brands,
    filterOptions.categories,
  ])

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
        <FilterToolbar
          search={search}
          onSearchChange={(value) => {
            setSearch(value)
            setPage(1)
          }}
          searchPlaceholder="Search product, SKU, model, brand..."
          activeCount={activeFilterCount}
          onOpenFilters={() => setFiltersOpen(true)}
          onReset={resetFilters}
          variant={theme}
        >
          <AppliedFilterChips chips={appliedFilterChips} variant={theme} />
        </FilterToolbar>

        {loading ? (
          <div
            className={`rounded-2xl border py-12 text-center text-sm ${isDark
                ? 'border-slate-800 text-slate-400'
                : 'border-slate-200 text-slate-500'
              }`}
          >
            Loading alerts...
          </div>
        ) : error ? (
          <div className="rounded-2xl border border-rose-200 bg-rose-50 p-8 text-center text-sm text-rose-700">
            {error}
          </div>
        ) : data && data.rows.length ? (
          <>
            <ProductTable
              rows={data.rows}
              variant={theme}
              onView={openProduct}
              getSort={getSort}
              getSortIndex={getSortIndex}
              onSort={cycleSort}
            />

            <div className="mt-4">
              <PaginationControls
                pagination={data.pagination}
                onPageChange={setPage}
                variant={theme}
              />
            </div>
          </>
        ) : (
          <div
            className={`rounded-2xl border p-8 text-center text-sm ${isDark
                ? 'border-slate-800 text-slate-400'
                : 'border-slate-200 text-slate-500'
              }`}
          >
            No inventory alerts found for the selected filters.
          </div>
        )}
      </SectionCard>

      <FilterDrawer
        open={filtersOpen}
        title="Inventory Alert Filters"
        activeCount={activeFilterCount}
        onClose={() => setFiltersOpen(false)}
        onReset={resetFilters}
        onApply={() => setFiltersOpen(false)}
        variant={theme}
      >
        <div className="grid grid-cols-1 gap-4">
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

      <ProductDrawer
        product={selectedProduct}
        loading={detailLoading}
        error={detailError}
        open={detailLoading || Boolean(detailError) || Boolean(selectedProduct)}
        onClose={() => {
          setSelectedProduct(null)
          setDetailError(null)
        }}
        variant={theme}
      />
    </div>
  )
}

function ProductDrawer({
  product,
  loading,
  error,
  open,
  onClose,
  variant,
}: {
  product: InventoryProductDetail | null
  loading: boolean
  error: string | null
  open: boolean
  onClose: () => void
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <RightDrawer
      open={open}
      title={product?.title ?? 'Product Details'}
      subtitle="Current stock, batches, movements, and serials."
      onClose={onClose}
      widthClassName="max-w-5xl"
    >
      {loading ? (
        <div className="rounded-2xl bg-slate-50 p-5 text-sm text-slate-500">
          Loading product details...
        </div>
      ) : error ? (
        <div className="rounded-2xl bg-rose-50 p-5 text-sm text-rose-700">
          {error}
        </div>
      ) : product ? (
        <div className="space-y-6">
          <div>
            <h2 className={`text-xl font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
              {product.title}
            </h2>
            <p className={`mt-1 text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
              {[
                product.sku,
                product.modelNumber,
                product.brandName,
                product.categoryName,
                product.subCategoryName,
              ]
                .filter(Boolean)
                .join(' / ')}
            </p>
          </div>

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
            {product.batches.length ? (
              product.batches.map((row) => (
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
              ))
            ) : (
              <tr>
                <td colSpan={7} className="px-4 py-6 text-center text-sm text-slate-500">
                  No open batches found.
                </td>
              </tr>
            )}
          </DetailTable>

          <DetailTable
            title="Recent Movements"
            variant={variant}
            headings={['Date', 'Type', 'Qty', 'Batch', 'Unit Cost', 'Reference']}
          >
            {product.movements.length ? (
              product.movements.map((row) => (
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
              ))
            ) : (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-sm text-slate-500">
                  No recent movements found.
                </td>
              </tr>
            )}
          </DetailTable>

          {product.isSerialized ? (
            <DetailTable
              title="Serial Numbers"
              variant={variant}
              headings={['Serial', 'Status', 'Batch', 'Received']}
            >
              {product.serials.length ? (
                product.serials.map((row) => (
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
                ))
              ) : (
                <tr>
                  <td colSpan={4} className="px-4 py-6 text-center text-sm text-slate-500">
                    No serial numbers found.
                  </td>
                </tr>
              )}
            </DetailTable>
          ) : null}
        </div>
      ) : null}
    </RightDrawer>
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
        className={`text-xs font-semibold uppercase tracking-[0.12em] ${isDark ? 'text-slate-500' : 'text-slate-400'
          }`}
      >
        {label}
      </div>
      <div
        className={`mt-2 font-data text-sm font-semibold ${isDark ? 'text-white' : 'text-slate-950'
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
  children: ReactNode
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <div>
      <h3
        className={`mb-3 text-sm font-semibold ${isDark ? 'text-white' : 'text-slate-950'
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
          <table className="w-full min-w-[860px] border-collapse">
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