import {
  Cell,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
} from 'recharts'
import { formatCurrency, formatLabel, formatNumber, toNumber } from '../../utils/format'

export type DonutChartItem = {
  label: string
  revenue: number | string
  orders: number | string
}

type DonutChartProps = {
  items: DonutChartItem[]
  metric: 'revenue' | 'orders'
}

type ChartDatum = {
  name: string
  value: number
  color: string
}

const COLORS = ['#7C3AED', '#22C55E', '#EAB308', '#EF4444', '#3B82F6', '#8B5CF6']

function renderPercentLabel({
  cx,
  cy,
  midAngle,
  outerRadius,
  percent,
}: {
  cx?: number
  cy?: number
  midAngle?: number
  outerRadius?: number
  percent?: number
}) {
  if (
    cx === undefined ||
    cy === undefined ||
    midAngle === undefined ||
    outerRadius === undefined ||
    percent === undefined
  ) {
    return null
  }

  if (percent < 0.04) return null

  const RADIAN = Math.PI / 180
  const radius = outerRadius + 16
  const x = cx + radius * Math.cos(-midAngle * RADIAN)
  const y = cy + radius * Math.sin(-midAngle * RADIAN)

  return (
    <text
      x={x}
      y={y}
      fill="#475569"
      textAnchor={x > cx ? 'start' : 'end'}
      dominantBaseline="central"
      fontSize="12"
      fontWeight="600"
    >
      {(percent * 100).toFixed(1)}%
    </text>
  )
}

export default function DonutChart({
  items,
  metric,
}: DonutChartProps) {
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
    color: item.color,
  }))

  return (
    <div className="w-full">
      <div className="mb-4 flex items-center justify-center">
        {metric === 'revenue' ? (
          <span className="inline-flex items-center rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-600">
            Total Revenue:&nbsp;
            <span className="font-semibold text-slate-900">
              {formatCurrency(totalRevenue)}
            </span>
          </span>
        ) : (
          <span className="inline-flex items-center rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-600">
            Total Orders:&nbsp;
            <span className="font-semibold text-slate-900">
              {formatNumber(totalOrders)}
            </span>
          </span>
        )}
      </div>

      <div className="grid items-center gap-6 lg:grid-cols-[300px_1fr]">
        {/* Donut */}
        <div className="mx-auto h-[280px] w-full max-w-[300px]">
          <ResponsiveContainer width="100%" height="100%">
            <PieChart>
              <Tooltip
                formatter={(value, _name, item) => {
                  const numericValue =
                    typeof value === 'number' ? value : Number(value ?? 0)

                  const label =
                    (item?.payload as { name?: string } | undefined)?.name ?? 'Channel'

                  if (metric === 'revenue') {
                    return [formatCurrency(numericValue), `${label} Revenue`]
                  }

                  return [formatNumber(numericValue), `${label} Orders`]
                }}
              />

              <Pie
                data={chartData}
                dataKey="value"
                nameKey="name"
                cx="50%"
                cy="50%"
                innerRadius={72}
                outerRadius={104}
                paddingAngle={3}
                stroke="#ffffff"
                strokeWidth={5}
                labelLine={false}
                label={renderPercentLabel}
              >
                {chartData.map((entry, index) => (
                  <Cell
                    key={`cell-${index}`}
                    fill={entry.color}
                  />
                ))}
              </Pie>
            </PieChart>
          </ResponsiveContainer>
        </div>

        {/* Legend on the right */}
        <div className="flex flex-col gap-3 lg:pr-4">
          {normalizedItems.map((item, index) => (
            <div
              key={`${item.label}-${index}`}
              className="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3"
            >
              <div className="flex items-center gap-3">
                <span
                  className="inline-block h-3 w-3 rounded-full"
                  style={{ backgroundColor: item.color }}
                />
                <span className="text-sm font-medium text-slate-700">
                  {item.label}
                </span>
              </div>

              <span className="text-sm font-semibold text-slate-900">
                {metric === 'revenue'
                  ? formatCurrency(item.revenue)
                  : formatNumber(item.orders)}
              </span>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}