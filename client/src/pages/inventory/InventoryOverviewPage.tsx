import { useCallback, useEffect, useState } from 'react'
import { Link, useOutletContext } from 'react-router-dom'
import SectionCard from '../../components/dashboard/SectionCard'
import StatCard from '../../components/dashboard/StatCard'
import { getInventoryOverview } from '../../api/inventory'
import type { DashboardOutletContext } from '../../layouts/DashboardLayout'
import type { InventoryOverviewResponse } from '../../types/inventory'
import { formatCompactNumber, formatCurrency, formatNumber } from '../../utils/format'
import {
  DEFAULT_INVENTORY_DATE_RANGE,
  INVENTORY_MAX_DATE,
  formatDate,
  formatDateRangeLabel,
  formatEnumLabel,
  normalizeDateRange,
} from './inventoryShared'

export default function InventoryOverviewPage() {
  const { setHeaderRange, setHeaderDateRangeControl, theme } =
    useOutletContext<DashboardOutletContext>()

  const [dateRange, setDateRange] = useState(DEFAULT_INVENTORY_DATE_RANGE)
  const [data, setData] = useState<InventoryOverviewResponse | null>(null)
  const [loading, setLoading] = useState(true)

  const isDark = theme === 'dark'

  const updateDateRange = useCallback((nextRange: { startDate: string; endDate: string }) => {
    setDateRange(normalizeDateRange(nextRange))
  }, [])

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
  }, [dateRange.startDate, dateRange.endDate, setHeaderRange, setHeaderDateRangeControl, updateDateRange])

  useEffect(() => {
    let active = true

    async function load() {
      setLoading(true)
      const response = await getInventoryOverview(dateRange)
      if (!active) return
      setData(response)
      setLoading(false)
    }

    load()

    return () => {
      active = false
    }
  }, [dateRange])

  if (loading || !data) {
    return (
      <SectionCard title="Inventory" description="Loading inventory data..." variant={theme}>
        <div className={`py-12 text-center text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
          Loading inventory...
        </div>
      </SectionCard>
    )
  }

  return (
    <div className="space-y-8">
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
        <StatCard title="Total Products" value={formatNumber(data.summary.totalProducts)} helperText="All SKUs" variant={theme} compact />
        <StatCard title="Active SKUs" value={formatNumber(data.summary.activeSkus)} helperText="Currently sellable" variant={theme} compact />
        <StatCard title="Inventory Value" value={formatCompactNumber(data.summary.inventoryValue)} secondaryValue={formatCurrency(data.summary.inventoryValue)} variant={theme} compact monoSecondary />
        <StatCard title="Low Stock" value={formatNumber(data.summary.lowStockItems)} helperText="1-5 units" variant={theme} compact />
        <StatCard title="Out of Stock" value={formatNumber(data.summary.outOfStockItems)} helperText="0 units" variant={theme} compact />
        <StatCard title="Serialized Units" value={formatNumber(data.summary.serializedUnits)} helperText="Available serials" variant={theme} compact />
      </div>

      <div className="grid gap-6 xl:grid-cols-[1fr_0.9fr]">
        <SectionCard title="Inventory by Category" description="Stock value and risk by category." variant={theme}>
          <div className="space-y-3">
            {data.categoryBreakdown.map((row) => (
              <div key={row.categoryName} className={['rounded-2xl border p-4', isDark ? 'border-slate-800 bg-slate-950/40' : 'border-slate-200 bg-white'].join(' ')}>
                <div className="flex items-center justify-between gap-4">
                  <div>
                    <div className={`font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>{row.categoryName}</div>
                    <div className={`mt-1 text-xs ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
                      {formatNumber(row.productCount)} products / {formatNumber(row.stockQty)} units
                    </div>
                  </div>
                  <div className="text-right">
                    <div className={`font-data text-sm font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>
                      {formatCurrency(row.inventoryValue)}
                    </div>
                    <div className={`mt-1 text-xs ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>
                      {row.lowStockCount} low / {row.outOfStockCount} out
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </SectionCard>

        <SectionCard
          title="Inventory Alerts"
          description="Top products needing attention."
          action={<Link to="/inventory/alerts" className={`text-sm font-semibold ${isDark ? 'text-blue-300' : 'text-blue-600'}`}>View all</Link>}
          variant={theme}
        >
          <div className="space-y-3">
            {data.alertsPreview.map((item) => (
              <div key={item.id} className={['rounded-2xl border p-4', isDark ? 'border-slate-800 bg-slate-950/40' : 'border-slate-200 bg-white'].join(' ')}>
                <div className="flex items-center justify-between gap-4">
                  <div className="min-w-0">
                    <div className={`truncate text-sm font-semibold ${isDark ? 'text-white' : 'text-slate-950'}`}>{item.title}</div>
                    <div className={`mt-1 text-xs ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>{item.sku}</div>
                  </div>
                  <div className="text-right">
                    <div className={`font-data text-lg font-bold ${item.stockQty === 0 ? 'text-rose-500' : isDark ? 'text-white' : 'text-slate-950'}`}>
                      {item.stockQty}
                    </div>
                    <div className={`text-[10px] uppercase tracking-[0.12em] ${isDark ? 'text-slate-500' : 'text-slate-400'}`}>units</div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </SectionCard>
      </div>

      <SectionCard title="Recent Stock Movements" description="Latest inventory activity in the selected date range." variant={theme}>
        <div className="overflow-hidden rounded-2xl border border-slate-200">
          <table className="w-full min-w-[900px] border-collapse">
            <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
              <tr>
                {['Date', 'Product', 'Movement', 'Qty', 'Batch', 'Unit Cost'].map((heading) => (
                  <th key={heading} className={['px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.1em]', isDark ? 'text-slate-500' : 'text-slate-400'].join(' ')}>
                    {heading}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className={['divide-y', isDark ? 'divide-slate-800 bg-slate-900' : 'divide-slate-100 bg-white'].join(' ')}>
              {data.recentMovements.map((row) => (
                <tr key={row.id}>
                  <td className="px-4 py-4 text-sm">{formatDate(row.movedAt)}</td>
                  <td className="px-4 py-4">
                    <div className="text-sm font-semibold">{row.productTitle}</div>
                    <div className="text-xs text-slate-400">{row.sku}</div>
                  </td>
                  <td className="px-4 py-4 text-sm">{formatEnumLabel(row.movementType)}</td>
                  <td className={`px-4 py-4 font-data text-sm font-semibold ${row.qtyChange >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>
                    {row.qtyChange >= 0 ? '+' : ''}
                    {row.qtyChange}
                  </td>
                  <td className="px-4 py-4 text-sm">{row.batchCode || '-'}</td>
                  <td className="px-4 py-4 font-data text-sm">{row.unitCost ? formatCurrency(row.unitCost) : '-'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </SectionCard>
    </div>
  )
}