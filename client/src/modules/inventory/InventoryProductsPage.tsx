import { useEffect, useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import { useOutletContext } from 'react-router-dom'
import SectionCard from '../../shared/components/SectionCard'
import RightDrawer from '../../shared/components/RightDrawer'
import DedicatedEntitySearch from '../../shared/components/DedicatedEntitySearch'
import FilterDrawer from '../../shared/components/FilterDrawer'
import FilterToolbar from '../../shared/components/FilterToolbar'
import AppliedFilterChips from '../../shared/components/AppliedFilterChips'
import { getInventoryProductDetail, getInventoryProducts } from './inventory.api'
import type { DashboardOutletContext } from '../../app/DashboardLayout'
import type {
  InventoryProductDetail,
  InventoryProductsResponse,
  InventoryProductRow,
} from './inventory.types'
import type { LookupResult } from '../../shared/types/lookup.types'
import { useMultiSort } from '../../shared/hooks/useMultiSort'
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

export default function InventoryProductsPage() {
  const { setHeaderRange, setHeaderDateRangeControl, theme } =
    useOutletContext<DashboardOutletContext>()

  const [search, setSearch] = useState('')
  const [statuses, setStatuses] = useState<string[]>([])
  const [category, setCategory] = useState('all')
  const [brand, setBrand] = useState('all')
  const [serialized, setSerialized] = useState('all')
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(10)
  const [data, setData] = useState<InventoryProductsResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [filtersOpen, setFiltersOpen] = useState(false)

  const [selectedProduct, setSelectedProduct] =
    useState<InventoryProductDetail | null>(null)
  const [detailLoading, setDetailLoading] = useState(false)
  const [detailError, setDetailError] = useState<string | null>(null)

  const { sortParam, cycleSort, getSort, getSortIndex } = useMultiSort(
    [{ field: 'product', direction: 'asc' }],
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

    async function load() {
      try {
        setLoading(true)
        setError(null)

        const response = await getInventoryProducts({
          startDate: DEFAULT_INVENTORY_DATE_RANGE.startDate,
          endDate: DEFAULT_INVENTORY_DATE_RANGE.endDate,
          search,
          statuses,
          category,
          brand,
          serialized,
          page,
          perPage,
          sort: sortParam,
        })

        if (!active) return
        setData(response)
      } catch {
        if (!active) return
        setError('Failed to load inventory products.')
      } finally {
        if (active) setLoading(false)
      }
    }

    load()

    return () => {
      active = false
    }
  }, [search, statuses, category, brand, serialized, page, perPage, sortParam])

  async function openProductById(id: number) {
    try {
      setDetailLoading(true)
      setDetailError(null)
      setSelectedProduct(null)

      const response = await getInventoryProductDetail(id)
      setSelectedProduct(response)
    } catch {
      setDetailError('Failed to load product details.')
    } finally {
      setDetailLoading(false)
    }
  }

  function openProduct(row: InventoryProductRow) {
    void openProductById(row.id)
  }

  function openLookupProduct(result: LookupResult) {
    if (result.type === 'inventory') {
      void openProductById(result.id)
    }
  }

  function resetFilters() {
    setSearch('')
    setStatuses([])
    setCategory('all')
    setBrand('all')
    setSerialized('all')
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
      },
    [data],
  )

  const activeFilterCount =
    statuses.length +
    (category !== 'all' ? 1 : 0) +
    (brand !== 'all' ? 1 : 0) +
    (serialized !== 'all' ? 1 : 0)

  const appliedFilterChips = useMemo(() => {
    const chips: string[] = []

    statuses.forEach((status) => {
      chips.push(`Status: ${formatEnumLabel(status)}`)
    })

    if (category !== 'all') {
      chips.push(`Category: ${getOptionName(filterOptions.categories, category)}`)
    }

    if (brand !== 'all') {
      chips.push(`Brand: ${getOptionName(filterOptions.brands, brand)}`)
    }

    if (serialized !== 'all') {
      chips.push(
        serialized === 'serialized'
          ? 'Serialization: Serialized'
          : 'Serialization: Non-serialized',
      )
    }

    return chips
  }, [
    brand,
    category,
    filterOptions.brands,
    filterOptions.categories,
    serialized,
    statuses,
  ])

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
          Inventory Products
        </h1>
        <p className={`mt-1 text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
          Dedicated inventory lookup, sortable product list, stock value, batches, movements, and serials.
        </p>
      </div>

      <DedicatedEntitySearch
        entity="inventory"
        placeholder="Find product by SKU, model, title, brand, or category..."
        onOpenResult={openLookupProduct}
        variant={theme}
      />

      <SectionCard
        title="Products"
        description="Inventory is not tied to the dashboard date range. Search here filters the table; direct lookup searches globally."
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
          <div className={`rounded-2xl border p-8 text-center text-sm ${isDark ? 'border-slate-800 text-slate-400' : 'border-slate-200 text-slate-500'}`}>
            Loading products...
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
          <div className={`rounded-2xl border p-8 text-center text-sm ${isDark ? 'border-slate-800 text-slate-400' : 'border-slate-200 text-slate-500'}`}>
            No products found for the selected filters.
          </div>
        )}
      </SectionCard>

      <FilterDrawer
        open={filtersOpen}
        title="Inventory Product Filters"
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

          <FilterLabel label="Serialization" variant={theme}>
            <select
              value={serialized}
              onChange={(event) => {
                setSerialized(event.target.value)
                setPage(1)
              }}
              className={inputClass}
            >
              <option value="all">All</option>
              <option value="serialized">Serialized</option>
              <option value="non_serialized">Non-serialized</option>
            </select>
          </FilterLabel>

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
        <div className="rounded-2xl bg-slate-50 p-5 text-sm text-slate-500">Loading product details...</div>
      ) : error ? (
        <div className="rounded-2xl bg-rose-50 p-5 text-sm text-rose-700">{error}</div>
      ) : product ? (
        <div className="space-y-5">
          <div>
            <h2 className={`text-xl font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>{product.title}</h2>
            <p className={`mt-1 text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
              {[product.sku, product.modelNumber, product.brandName, product.categoryName, product.subCategoryName]
                .filter(Boolean)
                .join(' / ')}
            </p>
          </div>

          <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <MiniBox label="Stock" value={`${formatNumber(product.stockQty)} units`} variant={variant} />
            <MiniBox label="Inventory Value" value={formatCurrency(product.inventoryValue)} variant={variant} />
            <MiniBox label="Avg Unit Cost" value={formatCurrency(product.avgUnitCost)} variant={variant} />
            <MiniBox label="Last Sold" value={formatDate(product.lastSoldAt)} variant={variant} />
          </div>

          <DetailTable
            title="Batches"
            headings={['Batch', 'Received', 'Qty Received', 'Qty Remaining', 'Unit Cost', 'Value', 'PO']}
            variant={variant}
          >
            {product.batches.map((row) => (
              <tr key={row.id}>
                <td className="px-3 py-3">{row.batchCode || '-'}</td>
                <td className="px-3 py-3">{formatDate(row.receivedAt)}</td>
                <td className="px-3 py-3">{formatNumber(row.qtyReceived)}</td>
                <td className="px-3 py-3">{formatNumber(row.qtyRemaining)}</td>
                <td className="px-3 py-3">{formatCurrency(row.unitCost)}</td>
                <td className="px-3 py-3">{formatCurrency(row.remainingValue)}</td>
                <td className="px-3 py-3">{row.purchaseOrderNumber || '-'}</td>
              </tr>
            ))}
          </DetailTable>

          <DetailTable
            title="Movements"
            headings={['Date', 'Type', 'Qty Change', 'Batch', 'Unit Cost', 'Reference']}
            variant={variant}
          >
            {product.movements.map((row) => (
              <tr key={row.id}>
                <td className="px-3 py-3">{formatDate(row.movedAt)}</td>
                <td className="px-3 py-3">{formatEnumLabel(row.movementType)}</td>
                <td className={`px-3 py-3 font-semibold ${row.qtyChange >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>
                  {row.qtyChange >= 0 ? '+' : ''}
                  {row.qtyChange}
                </td>
                <td className="px-3 py-3">{row.batchCode || '-'}</td>
                <td className="px-3 py-3">{row.unitCost ? formatCurrency(row.unitCost) : '-'}</td>
                <td className="px-3 py-3">{[row.referenceType, row.referenceId].filter(Boolean).join(' #') || '-'}</td>
              </tr>
            ))}
          </DetailTable>

          {product.isSerialized ? (
            <DetailTable
              title="Serials"
              headings={['Serial Number', 'Status', 'Batch', 'Received']}
              variant={variant}
            >
              {product.serials.map((row) => (
                <tr key={row.id}>
                  <td className="px-3 py-3 font-data">{row.serialNumber}</td>
                  <td className="px-3 py-3">{formatEnumLabel(row.status)}</td>
                  <td className="px-3 py-3">{row.batchCode || '-'}</td>
                  <td className="px-3 py-3">{formatDate(row.receivedAt)}</td>
                </tr>
              ))}
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
    <div className={`rounded-2xl border p-4 ${isDark ? 'border-slate-800 bg-slate-950' : 'border-slate-200 bg-slate-50'}`}>
      <p className={`text-xs font-semibold uppercase tracking-wide ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
        {label}
      </p>
      <p className={`mt-1 font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>{value}</p>
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
                  <th key={heading} className="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
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