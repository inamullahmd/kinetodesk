import { useEffect, useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import { Eye } from 'lucide-react'
import { useOutletContext } from 'react-router-dom'
import SectionCard from '../../shared/components/SectionCard'
import StatCard from '../../shared/components/StatCard'
import RightDrawer from '../../shared/components/RightDrawer'
import SortableHeader from '../../shared/components/SortableHeader'
import FilterDrawer from '../../shared/components/FilterDrawer'
import FilterToolbar from '../../shared/components/FilterToolbar'
import AppliedFilterChips from '../../shared/components/AppliedFilterChips'
import { getInventoryMovements, getInventoryProductDetail } from './inventory.api'
import type { DashboardOutletContext } from '../../app/DashboardLayout'
import type {
  InventoryMovementRow,
  InventoryMovementsResponse,
  InventoryProductDetail,
} from './inventory.types'
import type { SortItem } from '../../shared/types/sort.types'
import { useMultiSort } from '../../shared/hooks/useMultiSort'
import { formatCurrency, formatNumber, formatDate, formatEnumLabel } from '../../shared/utils/format'
import {
  CheckboxGroup,
  DEFAULT_INVENTORY_DATE_RANGE,
  FilterLabel,
  PaginationControls,
} from './inventoryShared'

export default function InventoryMovementsPage() {
  const { setHeaderRange, setHeaderDateRangeControl, theme } =
    useOutletContext<DashboardOutletContext>()

  const [startDate, setStartDate] = useState(DEFAULT_INVENTORY_DATE_RANGE.startDate)
  const [endDate, setEndDate] = useState(DEFAULT_INVENTORY_DATE_RANGE.endDate)
  const [search, setSearch] = useState('')
  const [movementTypes, setMovementTypes] = useState<string[]>([])
  const [referenceTypes, setReferenceTypes] = useState<string[]>([])
  const [direction, setDirection] = useState('all')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(10)

  const [data, setData] = useState<InventoryMovementsResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [filtersOpen, setFiltersOpen] = useState(false)

  const [selectedProduct, setSelectedProduct] =
    useState<InventoryProductDetail | null>(null)
  const [detailLoading, setDetailLoading] = useState(false)
  const [detailError, setDetailError] = useState<string | null>(null)

  const { sortParam, cycleSort, getSort, getSortIndex } = useMultiSort(
    [{ field: 'movedAt', direction: 'desc' }],
    { onChange: () => setPage(1) },
  )

  const isDark = theme === 'dark'

  useEffect(() => {
    setHeaderRange({
      startDate,
      endDate,
      label: `${formatDate(startDate)} - ${formatDate(endDate)}`,
    })
    setHeaderDateRangeControl(null)

    return () => {
      setHeaderRange(null)
      setHeaderDateRangeControl(null)
    }
  }, [endDate, setHeaderDateRangeControl, setHeaderRange, startDate])

  useEffect(() => {
    let active = true

    async function loadMovements() {
      try {
        setLoading(true)
        setError(null)

        const response = await getInventoryMovements({
          startDate,
          endDate,
          search,
          movementTypes,
          referenceTypes,
          direction,
          page,
          perPage,
          sort: sortParam,
        })

        if (!active) return
        setData(response)
      } catch {
        if (!active) return
        setError('Failed to load stock movements.')
      } finally {
        if (active) setLoading(false)
      }
    }

    loadMovements()

    return () => {
      active = false
    }
  }, [
    direction,
    endDate,
    movementTypes,
    page,
    perPage,
    referenceTypes,
    search,
    sortParam,
    startDate,
  ])

  async function openProductById(id: number) {
    try {
      setDetailLoading(true)
      setDetailError(null)
      setSelectedProduct(null)

      const response = await getInventoryProductDetail(id, {
        startDate,
        endDate,
      })

      setSelectedProduct(response)
    } catch {
      setDetailError('Failed to load product details.')
    } finally {
      setDetailLoading(false)
    }
  }

  function resetFilters() {
    setStartDate(DEFAULT_INVENTORY_DATE_RANGE.startDate)
    setEndDate(DEFAULT_INVENTORY_DATE_RANGE.endDate)
    setSearch('')
    setMovementTypes([])
    setReferenceTypes([])
    setDirection('all')
    setPerPage(10)
    setPage(1)
  }

  const filterOptions = useMemo(
    () =>
      data?.filterOptions ?? {
        movementTypes: [],
        referenceTypes: [],
        directions: ['inbound', 'outbound'],
      },
    [data],
  )

  const activeFilterCount =
    movementTypes.length +
    referenceTypes.length +
    (direction !== 'all' ? 1 : 0) +
    (startDate !== DEFAULT_INVENTORY_DATE_RANGE.startDate ? 1 : 0) +
    (endDate !== DEFAULT_INVENTORY_DATE_RANGE.endDate ? 1 : 0)

  const appliedFilterChips = useMemo(() => {
    const chips: string[] = []

    if (startDate !== DEFAULT_INVENTORY_DATE_RANGE.startDate) {
      chips.push(`Start: ${formatDate(startDate)}`)
    }

    if (endDate !== DEFAULT_INVENTORY_DATE_RANGE.endDate) {
      chips.push(`End: ${formatDate(endDate)}`)
    }

    movementTypes.forEach((item) => {
      chips.push(`Movement: ${formatEnumLabel(item)}`)
    })

    referenceTypes.forEach((item) => {
      chips.push(`Reference: ${formatEnumLabel(item)}`)
    })

    if (direction !== 'all') {
      chips.push(`Direction: ${formatEnumLabel(direction)}`)
    }

    return chips
  }, [direction, endDate, movementTypes, referenceTypes, startDate])

  const inputClass = [
    'h-10 rounded-xl border px-3 text-sm outline-none transition',
    isDark
      ? 'border-slate-800 bg-slate-950 text-slate-200 placeholder:text-slate-600 focus:border-blue-500'
      : 'border-slate-200 bg-white text-slate-700 placeholder:text-slate-400 focus:border-blue-500',
  ].join(' ')

  return (
    <div className="space-y-6">
      <div>
        <h1
          className={`text-3xl font-semibold tracking-tight ${
            isDark ? 'text-white' : 'text-slate-950'
          }`}
        >
          Stock Movements
        </h1>
        <p className={`mt-1 text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
          Full inventory movement audit by product, batch, reference, and quantity direction.
        </p>
      </div>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <StatCard
          title="Movements"
          value={formatNumber(data?.summary.totalMovements ?? 0)}
          helperText="Movement records"
          variant={theme}
          compact
        />

        <StatCard
          title="Inbound Units"
          value={formatNumber(data?.summary.inboundUnits ?? 0)}
          helperText="Positive quantity changes"
          variant={theme}
          compact
        />

        <StatCard
          title="Outbound Units"
          value={formatNumber(data?.summary.outboundUnits ?? 0)}
          helperText="Negative quantity changes"
          variant={theme}
          compact
        />

        <StatCard
          title="Net Change"
          value={formatNumber(data?.summary.netQtyChange ?? 0)}
          helperText="Inbound minus outbound"
          variant={theme}
          compact
        />

        <StatCard
          title="Value Moved"
          value={formatCurrency(data?.summary.inventoryValueMoved ?? 0)}
          helperText="Absolute movement value"
          variant={theme}
          compact
        />
      </div>

      <SectionCard
        title="Movement Ledger"
        description="Search, filter, sort, and inspect stock changes across all inventory products."
        variant={theme}
      >
        <FilterToolbar
          search={search}
          onSearchChange={(value) => {
            setSearch(value)
            setPage(1)
          }}
          searchPlaceholder="Search product, SKU, model, batch, reference..."
          activeCount={activeFilterCount}
          onOpenFilters={() => setFiltersOpen(true)}
          onReset={resetFilters}
          variant={theme}
        >
          <AppliedFilterChips chips={appliedFilterChips} variant={theme} />
        </FilterToolbar>

        {loading ? (
          <div
            className={`rounded-2xl border py-12 text-center text-sm ${
              isDark
                ? 'border-slate-800 text-slate-400'
                : 'border-slate-200 text-slate-500'
            }`}
          >
            Loading stock movements...
          </div>
        ) : error ? (
          <div className="rounded-2xl border border-rose-200 bg-rose-50 p-8 text-center text-sm text-rose-700">
            {error}
          </div>
        ) : data && data.rows.length ? (
          <>
            <MovementTable
              rows={data.rows}
              variant={theme}
              getSort={getSort}
              getSortIndex={getSortIndex}
              onSort={cycleSort}
              onViewProduct={(row) => {
                void openProductById(row.productId)
              }}
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
            className={`rounded-2xl border p-8 text-center text-sm ${
              isDark
                ? 'border-slate-800 text-slate-400'
                : 'border-slate-200 text-slate-500'
            }`}
          >
            No stock movements found for the selected filters.
          </div>
        )}
      </SectionCard>

      <FilterDrawer
        open={filtersOpen}
        title="Stock Movement Filters"
        activeCount={activeFilterCount}
        onClose={() => setFiltersOpen(false)}
        onReset={resetFilters}
        onApply={() => setFiltersOpen(false)}
        variant={theme}
      >
        <div className="grid grid-cols-1 gap-4">
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            <FilterLabel label="Start Date" variant={theme}>
              <input
                type="date"
                value={startDate}
                onChange={(event) => {
                  setStartDate(event.target.value)
                  setPage(1)
                }}
                className={inputClass}
              />
            </FilterLabel>

            <FilterLabel label="End Date" variant={theme}>
              <input
                type="date"
                value={endDate}
                onChange={(event) => {
                  setEndDate(event.target.value)
                  setPage(1)
                }}
                className={inputClass}
              />
            </FilterLabel>
          </div>

          <FilterLabel label="Direction" variant={theme}>
            <select
              value={direction}
              onChange={(event) => {
                setDirection(event.target.value)
                setPage(1)
              }}
              className={inputClass}
            >
              <option value="all">All directions</option>
              <option value="inbound">Inbound only</option>
              <option value="outbound">Outbound only</option>
            </select>
          </FilterLabel>

          <CheckboxGroup
            label="Movement Types"
            options={filterOptions.movementTypes.map((item) => ({
              label: formatEnumLabel(item),
              value: item,
            }))}
            selected={movementTypes}
            onChange={(value) => {
              setMovementTypes(value)
              setPage(1)
            }}
            variant={theme}
          />

          <CheckboxGroup
            label="Reference Types"
            options={filterOptions.referenceTypes.map((item) => ({
              label: formatEnumLabel(item),
              value: item,
            }))}
            selected={referenceTypes}
            onChange={(value) => {
              setReferenceTypes(value)
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

function MovementTable({
  rows,
  variant,
  getSort,
  getSortIndex,
  onSort,
  onViewProduct,
}: {
  rows: InventoryMovementRow[]
  variant: 'light' | 'dark'
  getSort: (field: string) => SortItem | undefined
  getSortIndex: (field: string) => number | undefined
  onSort: (field: string) => void
  onViewProduct: (row: InventoryMovementRow) => void
}) {
  const isDark = variant === 'dark'

  return (
    <div className="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
      <div className="overflow-x-auto">
        <table className="w-full min-w-[1260px] divide-y divide-slate-200 dark:divide-slate-800">
          <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
            <tr>
              <SortableHeader label="Date" field="movedAt" sort={getSort('movedAt')} sortIndex={getSortIndex('movedAt')} onSort={onSort} />
              <SortableHeader label="Product" field="product" sort={getSort('product')} sortIndex={getSortIndex('product')} onSort={onSort} />
              <SortableHeader label="SKU" field="sku" sort={getSort('sku')} sortIndex={getSortIndex('sku')} onSort={onSort} />
              <SortableHeader label="Brand" field="brand" sort={getSort('brand')} sortIndex={getSortIndex('brand')} onSort={onSort} />
              <SortableHeader label="Movement" field="movementType" sort={getSort('movementType')} sortIndex={getSortIndex('movementType')} onSort={onSort} />
              <SortableHeader label="Qty" field="qtyChange" sort={getSort('qtyChange')} sortIndex={getSortIndex('qtyChange')} onSort={onSort} align="right" />
              <SortableHeader label="Batch" field="batch" sort={getSort('batch')} sortIndex={getSortIndex('batch')} onSort={onSort} />
              <SortableHeader label="Unit Cost" field="unitCost" sort={getSort('unitCost')} sortIndex={getSortIndex('unitCost')} onSort={onSort} align="right" />
              <SortableHeader label="Value" field="movementValue" sort={getSort('movementValue')} sortIndex={getSortIndex('movementValue')} onSort={onSort} align="right" />
              <SortableHeader label="Reference" field="reference" sort={getSort('reference')} sortIndex={getSortIndex('reference')} onSort={onSort} />
              <th className="px-4 py-3" />
            </tr>
          </thead>

          <tbody className={`divide-y ${isDark ? 'divide-slate-800 bg-slate-900' : 'divide-slate-100 bg-white'}`}>
            {rows.map((row) => (
              <tr key={row.id} className={isDark ? 'hover:bg-slate-950/70' : 'hover:bg-slate-50'}>
                <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                  {formatDate(row.movedAt)}
                </td>

                <td className="px-4 py-4">
                  <p className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
                    {row.productTitle}
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
                  {formatEnumLabel(row.movementType)}
                </td>

                <td
                  className={[
                    'px-4 py-4 text-right font-data text-sm font-semibold',
                    row.qtyChange >= 0 ? 'text-emerald-600' : 'text-rose-600',
                  ].join(' ')}
                >
                  {row.qtyChange >= 0 ? '+' : ''}
                  {formatNumber(row.qtyChange)}
                </td>

                <td className={`px-4 py-4 font-data text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                  {row.batchCode || '-'}
                </td>

                <td className={`px-4 py-4 text-right font-data text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                  {row.unitCost !== null && row.unitCost !== undefined
                    ? formatCurrency(row.unitCost)
                    : '-'}
                </td>

                <td className={`px-4 py-4 text-right font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
                  {formatCurrency(row.movementValue)}
                </td>

                <td className={`px-4 py-4 text-sm ${isDark ? 'text-slate-300' : 'text-slate-600'}`}>
                  {[row.referenceType, row.referenceId].filter(Boolean).join(' #') || '-'}
                </td>

                <td className="px-4 py-4 text-right">
                  <button
                    type="button"
                    onClick={() => onViewProduct(row)}
                    className={[
                      'inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold transition',
                      isDark
                        ? 'border-slate-700 bg-slate-950 text-slate-200 hover:bg-slate-900'
                        : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
                    ].join(' ')}
                  >
                    <Eye className="h-4 w-4" />
                    Product
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

          <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <MiniBox label="Stock" value={`${formatNumber(product.stockQty)} units`} variant={variant} />
            <MiniBox label="Inventory Value" value={formatCurrency(product.inventoryValue)} variant={variant} />
            <MiniBox label="Avg Cost" value={formatCurrency(product.avgUnitCost)} variant={variant} />
            <MiniBox label="Last Sold" value={formatDate(product.lastSoldAt)} variant={variant} />
          </div>

          <DetailTable
            title="Recent Movements"
            headings={['Date', 'Type', 'Qty', 'Batch', 'Unit Cost', 'Reference']}
            variant={variant}
          >
            {product.movements.length ? (
              product.movements.map((row) => (
                <tr key={row.id}>
                  <td className="px-4 py-3">{formatDate(row.movedAt)}</td>
                  <td className="px-4 py-3">{formatEnumLabel(row.movementType)}</td>
                  <td
                    className={[
                      'px-4 py-3 font-data font-semibold',
                      row.qtyChange >= 0 ? 'text-emerald-600' : 'text-rose-600',
                    ].join(' ')}
                  >
                    {row.qtyChange >= 0 ? '+' : ''}
                    {formatNumber(row.qtyChange)}
                  </td>
                  <td className="px-4 py-3">{row.batchCode || '-'}</td>
                  <td className="px-4 py-3 font-data">
                    {row.unitCost ? formatCurrency(row.unitCost) : '-'}
                  </td>
                  <td className="px-4 py-3">
                    {[row.referenceType, row.referenceId].filter(Boolean).join(' #') || '-'}
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
  children: ReactNode
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <div>
      <h3 className={`mb-3 text-sm font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
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