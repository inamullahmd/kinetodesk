import {
  Cell,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
} from 'recharts'
import type {
  Formatter,
  NameType,
  ValueType,
} from 'recharts/types/component/DefaultTooltipContent'
import { formatCurrency, formatLabel, formatNumber, toNumber } from '../../utils/format'

export type DonutChartItem = {
  label: string
  revenue: number | string
  orders: number | string
}

type DonutChartProps = {
  items: DonutChartItem[]
  metric: 'revenue' | 'orders'
  variant?: 'light' | 'dark'
}

type ChartDatum = {
  name: string
  value: number
  revenue: number
  orders: number
  percentage: number
  color: string
}

type DonutLabelProps = {
  cx?: number
  cy?: number
  midAngle?: number
  innerRadius?: number
  percent?: number
  payload?: ChartDatum
}

const COLORS = ['#4F46E5', '#55B86A', '#7C93F6']
const RADIAN = Math.PI / 180

function renderInnerNearSegmentLabel({
  cx = 0,
  cy = 0,
  midAngle = 0,
  innerRadius = 72,
  percent,
  payload,
}: DonutLabelProps) {
  if (!percent || percent < 0.06 || !payload) return null

  const radius = innerRadius - 26
  const x = cx + radius * Math.cos(-midAngle * RADIAN)
  const y = cy + radius * Math.sin(-midAngle * RADIAN)

  return (
    <text
      x={x}
      y={y}
      fill={payload.color}
      textAnchor="middle"
      dominantBaseline="central"
      fontSize={12}
      fontWeight={800}
      fontFamily='"JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace'
      style={{
        pointerEvents: 'none',
      }}
    >
      {(percent * 100).toFixed(1)}%
    </text>
  )
}

export default function DonutChart({
  items,
  metric,
  variant = 'light',
}: DonutChartProps) {
  const isDark = variant === 'dark'

  const normalizedItems = items
    .map((item, index) => ({
      label: formatLabel(item.label),
      revenue: toNumber(item.revenue),
      orders: toNumber(item.orders),
      color: COLORS[index % COLORS.length],
    }))
    .sort((a, b) => {
      const aValue = metric === 'revenue' ? a.revenue : a.orders
      const bValue = metric === 'revenue' ? b.revenue : b.orders

      return bValue - aValue
    })

  const totalRevenue = normalizedItems.reduce((sum, item) => sum + item.revenue, 0)
  const totalOrders = normalizedItems.reduce((sum, item) => sum + item.orders, 0)

  const chartData: ChartDatum[] = normalizedItems.map((item) => ({
    name: item.label,
    value: metric === 'revenue' ? item.revenue : item.orders,
    revenue: item.revenue,
    orders: item.orders,
    percentage:
      metric === 'revenue'
        ? totalRevenue > 0
          ? (item.revenue / totalRevenue) * 100
          : 0
        : totalOrders > 0
          ? (item.orders / totalOrders) * 100
          : 0,
    color: item.color,
  }))

  const tooltipFormatter: Formatter<ValueType, NameType> = (value, _name, item) => {
    const datum = item?.payload as ChartDatum | undefined
    const numericValue = typeof value === 'number' ? value : Number(value ?? 0)

    return [
      metric === 'revenue'
        ? formatCurrency(numericValue)
        : `${formatNumber(numericValue)} orders`,
      datum?.name ?? '',
    ]
  }

  if (!chartData.length) {
    return (
      <div
        className={`rounded-2xl border px-4 py-8 text-center text-sm ${
          isDark
            ? 'border-slate-800 bg-slate-950 text-slate-400'
            : 'border-slate-200 bg-slate-50 text-slate-500'
        }`}
      >
        No channel data available.
      </div>
    )
  }

  const totalLabel = metric === 'revenue' ? 'Total Revenue' : 'Total Orders'
  const totalValue =
    metric === 'revenue'
      ? formatCurrency(totalRevenue)
      : formatNumber(totalOrders)

  return (
    <div className="w-full">
      <div className="mb-4">
  <div
    className={[
      'text-[11px] font-semibold uppercase tracking-[0.12em]',
      isDark ? 'text-slate-500' : 'text-slate-400',
    ].join(' ')}
  >
    {totalLabel}
  </div>

  <div
    className={[
      'mt-1 font-data text-[15px] font-semibold',
      isDark ? 'text-slate-200' : 'text-slate-800',
    ].join(' ')}
  >
    {totalValue}
  </div>
</div>

      <div className="mx-auto h-[255px] w-full max-w-[290px]">
        <ResponsiveContainer width="100%" height="100%">
          <PieChart>
            <Tooltip
              contentStyle={{
                background: isDark ? '#0F172A' : '#FFFFFF',
                border: isDark
                  ? '1px solid #1E293B'
                  : '1px solid #E2E8F0',
                borderRadius: '16px',
                color: isDark ? '#FFFFFF' : '#0F172A',
              }}
              formatter={tooltipFormatter}
            />

            <Pie
              data={chartData}
              dataKey="value"
              nameKey="name"
              innerRadius={74}
              outerRadius={112}
              paddingAngle={4}
              cornerRadius={7}
              stroke={isDark ? '#0F172A' : '#FFFFFF'}
              strokeWidth={4}
              labelLine={false}
              label={renderInnerNearSegmentLabel}
            >
              {chartData.map((entry) => (
                <Cell key={entry.name} fill={entry.color} />
              ))}
            </Pie>
          </PieChart>
        </ResponsiveContainer>
      </div>

      <div className="mt-3 grid gap-3">
        {chartData.map((item) => (
          <div
            key={item.name}
            className={`rounded-2xl border px-4 py-3 ${
              isDark
                ? 'border-slate-800 bg-slate-950'
                : 'border-slate-200 bg-white'
            }`}
          >
            <div className="flex items-center justify-between gap-4">
              <div className="flex min-w-0 items-center gap-3">
                <span
                  className="h-3.5 w-3.5 shrink-0 rounded-full"
                  style={{ backgroundColor: item.color }}
                />

                <div className="min-w-0">
                  <div
                    className={`truncate text-sm font-semibold ${
                      isDark ? 'text-white' : 'text-slate-900'
                    }`}
                  >
                    {item.name}
                  </div>

                  <div
                    className={`mt-0.5 text-xs font-medium ${
                      isDark ? 'text-slate-300' : 'text-slate-600'
                    }`}
                  >
                    {metric === 'revenue'
                      ? formatCurrency(item.revenue)
                      : `${formatNumber(item.orders)} orders`}
                  </div>
                </div>
              </div>

              <div className="text-right">
                <div
                  className={`font-data text-sm font-semibold ${
                    isDark ? 'text-white' : 'text-slate-900'
                  }`}
                >
                  {item.percentage.toFixed(1)}%
                </div>

                <div
                  className={`text-[10px] uppercase tracking-[0.14em] ${
                    isDark ? 'text-slate-500' : 'text-slate-400'
                  }`}
                >
                  share
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}