import {
  Cell,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
} from 'recharts'
import { formatCurrency, formatLabel, formatNumber, toNumber } from '../../utils/format'

export type DoubleDonutChartItem = {
  label: string
  orders: number | string
  revenue: number | string
}

type DoubleDonutChartProps = {
  items: DoubleDonutChartItem[]
}

type ChartDatum = {
  name: string
  value: number
  ring: 'orders' | 'revenue'
}

const COLORS = ['#7C3AED', '#22C55E', '#EAB308', '#EF4444', '#3B82F6', '#8B5CF6']

export default function DoubleDonutChart({ items }: DoubleDonutChartProps) {
  const normalizedItems = items
    .map((item) => ({
      label: formatLabel(item.label),
      orders: toNumber(item.orders),
      revenue: toNumber(item.revenue),
    }))
    .sort((a, b) => b.revenue - a.revenue)

  const revenueData: ChartDatum[] = normalizedItems.map((item) => ({
    name: item.label,
    value: item.revenue,
    ring: 'revenue',
  }))

  const ordersData: ChartDatum[] = normalizedItems.map((item) => ({
    name: item.label,
    value: item.orders,
    ring: 'orders',
  }))

  const totalRevenue = normalizedItems.reduce((sum, item) => sum + item.revenue, 0)
  const totalOrders = normalizedItems.reduce((sum, item) => sum + item.orders, 0)

  return (
    <div className="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)] xl:items-center">
      {/* Chart side */}
      <div className="mx-auto w-full max-w-[380px]">
        <div className="relative h-[320px] w-full">
          <ResponsiveContainer width="100%" height="100%">
            <PieChart>
              <Tooltip
                formatter={(value, _name, item) => {
                  const numericValue =
                    typeof value === 'number' ? value : Number(value ?? 0)

                  const payload = item?.payload as ChartDatum | undefined
                  const ring = payload?.ring
                  const label = payload?.name ?? 'Channel'

                  if (ring === 'revenue') {
                    return [formatCurrency(numericValue), `${label} Revenue`]
                  }

                  return [formatNumber(numericValue), `${label} Orders`]
                }}
              />

              {/* Outer ring = Revenue */}
              <Pie
                data={revenueData}
                dataKey="value"
                nameKey="name"
                cx="50%"
                cy="50%"
                innerRadius={88}
                outerRadius={118}
                paddingAngle={2}
                stroke="#ffffff"
                strokeWidth={4}
              >
                {revenueData.map((_, index) => (
                  <Cell
                    key={`revenue-${index}`}
                    fill={COLORS[index % COLORS.length]}
                  />
                ))}
              </Pie>

              {/* Inner ring = Orders */}
              <Pie
                data={ordersData}
                dataKey="value"
                nameKey="name"
                cx="50%"
                cy="50%"
                innerRadius={56}
                outerRadius={82}
                paddingAngle={2}
                stroke="#ffffff"
                strokeWidth={4}
              >
                {ordersData.map((_, index) => (
                  <Cell
                    key={`orders-${index}`}
                    fill={COLORS[index % COLORS.length]}
                    fillOpacity={0.35}
                  />
                ))}
              </Pie>
            </PieChart>
          </ResponsiveContainer>

          {/* Center content */}
          <div className="pointer-events-none absolute left-1/2 top-1/2 flex w-[150px] -translate-x-1/2 -translate-y-1/2 flex-col items-center justify-center text-center">
            <p className="text-[11px] font-medium uppercase tracking-[0.08em] text-slate-400">
              Total Revenue
            </p>
            <p className="mt-1 text-[1.65rem] font-bold leading-tight tracking-[0.01em] text-slate-950">
              {formatCurrency(totalRevenue)}
            </p>
            <p className="mt-1 text-sm text-slate-500">
              {formatNumber(totalOrders)} Orders
            </p>
          </div>
        </div>

        <div className="mt-2 flex items-center justify-center gap-3">
          <div className="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">
            <span className="inline-block h-2.5 w-2.5 rounded-full bg-slate-800" />
            Revenue
          </div>
          <div className="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">
            <span className="inline-block h-2.5 w-2.5 rounded-full bg-slate-400" />
            Orders
          </div>
        </div>
      </div>

      {/* Breakdown side */}
      <div className="min-w-0">
        <div className="rounded-2xl border border-slate-200 bg-slate-50/60">
          <div className="grid grid-cols-[minmax(0,1.4fr)_1fr_110px_90px] gap-4 border-b border-slate-200 px-5 py-4 text-xs font-semibold uppercase tracking-[0.08em] text-slate-400">
            <span>Channel</span>
            <span>Revenue</span>
            <span className="text-right">Share</span>
            <span className="text-right">Orders</span>
          </div>

          <div className="divide-y divide-slate-200">
            {normalizedItems.map((item, index) => {
              const color = COLORS[index % COLORS.length]
              const revenueShare = totalRevenue > 0 ? (item.revenue / totalRevenue) * 100 : 0

              return (
                <div
                  key={`${item.label}-${index}`}
                  className="grid grid-cols-[minmax(0,1.4fr)_1fr_110px_90px] items-center gap-4 px-5 py-4"
                >
                  <div className="min-w-0">
                    <div className="flex items-center gap-3">
                      <span
                        className="inline-block h-3 w-3 shrink-0 rounded-full"
                        style={{ backgroundColor: color }}
                      />
                      <span className="truncate text-sm font-semibold text-slate-900">
                        {item.label}
                      </span>
                    </div>
                  </div>

                  <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-slate-900">
                      {formatCurrency(item.revenue)}
                    </p>
                    <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-200">
                      <div
                        className="h-full rounded-full"
                        style={{
                          width: `${revenueShare}%`,
                          backgroundColor: color,
                        }}
                      />
                    </div>
                  </div>

                  <div className="text-right">
                    <span className="inline-flex rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">
                      {revenueShare.toFixed(1)}%
                    </span>
                  </div>

                  <div className="text-right text-sm font-semibold text-slate-900">
                    {formatNumber(item.orders)}
                  </div>
                </div>
              )
            })}
          </div>
        </div>
      </div>
    </div>
  )
}