import { useEffect, useState } from 'react'
import { Link, useOutletContext } from 'react-router-dom'
import SectionCard from '../../shared/components/SectionCard'
import StatCard from '../../shared/components/StatCard'
import { getInventoryOverview } from './inventory.api'
import type { DashboardOutletContext } from '../../app/DashboardLayout'
import type { InventoryOverviewResponse, InventoryProductRow } from './inventory.types'
import {
  formatCompactNumber,
  formatCurrency,
  formatNumber,
  formatEnumLabel,
} from '../../shared/utils/format'
import {
  DEFAULT_INVENTORY_DATE_RANGE,
} from './inventoryShared'

function ProductStatusBadge({
  status,
  variant,
}: {
  status: string
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'
  const normalized = status.toLowerCase()

  const className =
    normalized === 'out_of_stock'
      ? isDark
        ? 'bg-rose-500/10 text-rose-300 ring-rose-500/20'
        : 'bg-rose-50 text-rose-700 ring-rose-200'
      : normalized === 'low_stock'
        ? isDark
          ? 'bg-amber-500/10 text-amber-300 ring-amber-500/20'
          : 'bg-amber-50 text-amber-700 ring-amber-200'
        : normalized === 'stale_stock'
          ? isDark
            ? 'bg-violet-500/10 text-violet-300 ring-violet-500/20'
            : 'bg-violet-50 text-violet-700 ring-violet-200'
          : normalized === 'inactive'
            ? isDark
              ? 'bg-slate-800 text-slate-300 ring-slate-700'
              : 'bg-slate-100 text-slate-600 ring-slate-200'
            : isDark
              ? 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/20'
              : 'bg-emerald-50 text-emerald-700 ring-emerald-200'

  return (
    <span
      className={[
        'inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold leading-none ring-1 ring-inset',
        className,
      ].join(' ')}
    >
      {formatEnumLabel(status)}
    </span>
  )
}

function AlertProductCard({
  item,
  variant,
}: {
  item: InventoryProductRow
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <div
      className={[
        'rounded-2xl border p-4',
        isDark ? 'border-slate-800 bg-slate-950/40' : 'border-slate-200 bg-white',
      ].join(' ')}
    >
      <div className="flex items-start justify-between gap-4">
        <div className="min-w-0">
          <div
            className={`truncate text-sm font-semibold ${isDark ? 'text-white' : 'text-slate-950'
              }`}
          >
            {item.title}
          </div>

          <div
            className={`mt-1 text-xs ${isDark ? 'text-slate-400' : 'text-slate-500'
              }`}
          >
            {[item.sku, item.brandName, item.modelNumber].filter(Boolean).join(' / ') || '-'}
          </div>

          <div className="mt-2">
            <ProductStatusBadge status={item.alertType ?? item.status} variant={variant} />
          </div>
        </div>

        <div className="shrink-0 text-right">
          <div
            className={[
              'font-data text-lg font-bold',
              item.currentStock <= 0
                ? 'text-rose-500'
                : isDark
                  ? 'text-white'
                  : 'text-slate-950',
            ].join(' ')}
          >
            {formatNumber(item.currentStock)}
          </div>

          <div
            className={`text-[10px] uppercase tracking-[0.12em] ${isDark ? 'text-slate-500' : 'text-slate-400'
              }`}
          >
            units
          </div>
        </div>
      </div>
    </div>
  )
}

export default function InventoryOverviewPage() {
  const { setHeaderRange, setHeaderDateRangeControl, theme } =
    useOutletContext<DashboardOutletContext>()

  const [dateRange] = useState(DEFAULT_INVENTORY_DATE_RANGE)
  const [data, setData] = useState<InventoryOverviewResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

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

        const response = await getInventoryOverview(dateRange)

        if (!active) return

        setData(response)
      } catch {
        if (!active) return

        setError('Failed to load inventory overview.')
      } finally {
        if (active) {
          setLoading(false)
        }
      }
    }

    load()

    return () => {
      active = false
    }
  }, [dateRange])

  if (loading) {
    return (
      <SectionCard title="Inventory" description="Loading inventory data..." variant={theme}>
        <div
          className={`py-12 text-center text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'
            }`}
        >
          Loading inventory...
        </div>
      </SectionCard>
    )
  }

  if (error || !data) {
    return (
      <SectionCard title="Inventory" description="Inventory overview could not be loaded." variant={theme}>
        <div className="rounded-2xl border border-rose-200 bg-rose-50 p-8 text-center text-sm text-rose-700">
          {error ?? 'No inventory data available.'}
        </div>
      </SectionCard>
    )
  }

  return (
    <div className="space-y-8">
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
        <StatCard
          title="Total Products"
          value={formatNumber(data.summary.totalProducts)}
          helperText="All SKUs"
          variant={theme}
          compact
        />

        <StatCard
          title="Active SKUs"
          value={formatNumber(data.summary.activeSkus)}
          helperText="Currently sellable"
          variant={theme}
          compact
        />

        <StatCard
          title="Inventory Value"
          value={formatCompactNumber(data.summary.inventoryValue)}
          secondaryValue={formatCurrency(data.summary.inventoryValue)}
          variant={theme}
          compact
          monoSecondary
        />

        <StatCard
          title="Low Stock"
          value={formatNumber(data.summary.lowStockItems)}
          helperText="1-5 units"
          variant={theme}
          compact
        />

        <StatCard
          title="Out of Stock"
          value={formatNumber(data.summary.outOfStock)}
          helperText="0 units"
          variant={theme}
          compact
        />

        <StatCard
          title="Serialized Units"
          value={formatNumber(data.summary.serializedUnits)}
          helperText="Available serials"
          variant={theme}
          compact
        />
      </div>

      <div className="grid gap-6 xl:grid-cols-[1fr_0.9fr]">
        <SectionCard
          title="Inventory by Category"
          description="Stock value and risk by category."
          variant={theme}
        >
          <div className="space-y-3">
            {data.categoryBreakdown.length ? (
              data.categoryBreakdown.map((row) => (
                <div
                  key={row.categoryName}
                  className={[
                    'rounded-2xl border p-4',
                    isDark
                      ? 'border-slate-800 bg-slate-950/40'
                      : 'border-slate-200 bg-white',
                  ].join(' ')}
                >
                  <div className="flex items-center justify-between gap-4">
                    <div>
                      <div
                        className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'
                          }`}
                      >
                        {row.categoryName}
                      </div>

                      <div
                        className={`mt-1 text-xs ${isDark ? 'text-slate-400' : 'text-slate-500'
                          }`}
                      >
                        {formatNumber(row.productCount)} products /{' '}
                        {formatNumber(row.stockQty)} units
                      </div>
                    </div>

                    <div className="text-right">
                      <div
                        className={`font-data text-sm font-semibold ${isDark ? 'text-white' : 'text-slate-950'
                          }`}
                      >
                        {formatCurrency(row.inventoryValue)}
                      </div>

                      <div
                        className={`mt-1 text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'
                          }`}
                      >
                        Inventory value
                      </div>
                    </div>
                  </div>
                </div>
              ))
            ) : (
              <div
                className={`rounded-2xl border p-8 text-center text-sm ${isDark
                    ? 'border-slate-800 text-slate-400'
                    : 'border-slate-200 text-slate-500'
                  }`}
              >
                No category data available.
              </div>
            )}
          </div>
        </SectionCard>

        <SectionCard
          title="Inventory Alerts"
          description="Top products needing attention."
          action={
            <Link
              to="/inventory/alerts"
              className={`text-sm font-semibold ${isDark ? 'text-blue-300' : 'text-blue-600'
                }`}
            >
              View all
            </Link>
          }
          variant={theme}
        >
          <div className="space-y-3">
            {data.lowStockProducts.length ? (
              data.lowStockProducts.map((item) => (
                <AlertProductCard key={item.id} item={item} variant={theme} />
              ))
            ) : (
              <div
                className={`rounded-2xl border p-8 text-center text-sm ${isDark
                    ? 'border-slate-800 text-slate-400'
                    : 'border-slate-200 text-slate-500'
                  }`}
              >
                No low-stock products found.
              </div>
            )}
          </div>
        </SectionCard>
      </div>

      <SectionCard
        title="Inventory Activity"
        description="Open a product from Inventory Products or Inventory Alerts to view batches, serials, and stock movements."
        variant={theme}
        action={
          <Link
            to="/inventory/products"
            className={`text-sm font-semibold ${isDark ? 'text-blue-300' : 'text-blue-600'
              }`}
          >
            Browse products
          </Link>
        }
      >
        <div
          className={[
            'rounded-2xl border p-6',
            isDark
              ? 'border-slate-800 bg-slate-950/40 text-slate-400'
              : 'border-slate-200 bg-white text-slate-500',
          ].join(' ')}
        >
          <div className="text-sm">
            Product-level stock movements are available in each product detail drawer.
          </div>

          <div className="mt-3 grid gap-3 md:grid-cols-3">
            <InfoBox label="Batches" value="Open product detail" variant={theme} />
            <InfoBox label="Movements" value="Recent movement history" variant={theme} />
            <InfoBox label="Serials" value="Serialized product tracking" variant={theme} />
          </div>
        </div>
      </SectionCard>
    </div>
  )
}

function InfoBox({
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
      <div
        className={`text-xs font-semibold uppercase tracking-[0.12em] ${isDark ? 'text-slate-500' : 'text-slate-400'
          }`}
      >
        {label}
      </div>

      <div
        className={`mt-2 text-sm font-semibold ${isDark ? 'text-white' : 'text-slate-950'
          }`}
      >
        {value}
      </div>
    </div>
  )
}