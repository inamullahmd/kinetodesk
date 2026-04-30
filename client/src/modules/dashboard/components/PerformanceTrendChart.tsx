import {
  Area,
  CartesianGrid,
  ComposedChart,
  Line,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { formatCurrency } from '../../../shared/utils/format'

type SeriesKey = 'revenue' | 'profit'

type PerformanceTrendChartProps = {
  categories: string[]
  revenue: number[]
  profit: number[]
  visibleSeries: Record<SeriesKey, boolean>
  variant?: 'light' | 'dark'
  height?: number
}

type TrendPoint = {
  label: string
  revenue: number
  profit: number
}

type TooltipPayloadItem = {
  dataKey?: unknown
  value?: unknown
  color?: unknown
  name?: unknown
}

type CustomTooltipProps = {
  active?: boolean
  payload?: readonly TooltipPayloadItem[]
  label?: string
  isDark: boolean
}

const REVENUE_COLOR = '#4F46E5'
const PROFIT_COLOR = '#55B86A'

function toTooltipNumber(value: unknown) {
  if (typeof value === 'number') return value

  if (typeof value === 'string') {
    const parsed = Number(value)
    return Number.isFinite(parsed) ? parsed : 0
  }

  return 0
}

function formatCompactCurrency(value: number) {
  const sign = value < 0 ? '-' : ''
  const abs = Math.abs(value)

  if (abs >= 1_000_000_000) {
    return `${sign}$${(abs / 1_000_000_000).toFixed(1)}B`
  }

  if (abs >= 1_000_000) {
    return `${sign}$${(abs / 1_000_000).toFixed(1)}M`
  }

  if (abs >= 1_000) {
    return `${sign}$${(abs / 1_000).toFixed(0)}K`
  }

  return `${sign}$${abs.toFixed(0)}`
}

function formatXAxisLabel(label: string) {
  const parsed = new Date(`${label} 1`)

  if (!Number.isNaN(parsed.getTime())) {
    return parsed.toLocaleDateString('en-US', {
      month: 'short',
      year: '2-digit',
    })
  }

  return label
}

function CustomTooltip({
  active,
  payload,
  label,
  isDark,
}: CustomTooltipProps) {
  if (!active || !payload?.length) return null

  const showRevenue = payload.some((item) => item.dataKey === 'revenue')
  const showProfit = payload.some((item) => item.dataKey === 'profit')

  const revenueValue = toTooltipNumber(
    payload.find((item) => item.dataKey === 'revenue')?.value
  )

  const profitValue = toTooltipNumber(
    payload.find((item) => item.dataKey === 'profit')?.value
  )

  return (
    <div
      className={[
        'rounded-2xl border px-4 py-3 shadow-lg',
        isDark
          ? 'border-slate-800 bg-slate-950 text-white'
          : 'border-slate-200 bg-white text-slate-900',
      ].join(' ')}
    >
      <div
        className={[
          'mb-2 text-xs font-semibold uppercase tracking-[0.12em]',
          isDark ? 'text-slate-500' : 'text-slate-400',
        ].join(' ')}
      >
        {label}
      </div>

      <div className="space-y-2">
        {showRevenue ? (
          <div className="flex items-center justify-between gap-8">
            <div className="flex items-center gap-2">
              <span
                className="h-2.5 w-2.5 rounded-full"
                style={{ backgroundColor: REVENUE_COLOR }}
              />
              <span className={isDark ? 'text-slate-300' : 'text-slate-600'}>
                Revenue
              </span>
            </div>

            <span className="font-data font-semibold">
              {formatCurrency(revenueValue)}
            </span>
          </div>
        ) : null}

        {showProfit ? (
          <div className="flex items-center justify-between gap-8">
            <div className="flex items-center gap-2">
              <span
                className="h-2.5 w-2.5 rounded-full"
                style={{ backgroundColor: PROFIT_COLOR }}
              />
              <span className={isDark ? 'text-slate-300' : 'text-slate-600'}>
                Profit
              </span>
            </div>

            <span className="font-data font-semibold">
              {formatCurrency(profitValue)}
            </span>
          </div>
        ) : null}
      </div>
    </div>
  )
}

export default function PerformanceTrendChart({
  categories,
  revenue,
  profit,
  visibleSeries,
  variant = 'light',
  height = 380,
}: PerformanceTrendChartProps) {
  const isDark = variant === 'dark'

  const chartData: TrendPoint[] = categories.map((label, index) => ({
    label,
    revenue: revenue[index] ?? 0,
    profit: profit[index] ?? 0,
  }))

  const hasSourceData = chartData.some(
    (item) => item.revenue > 0 || item.profit > 0
  )

  const hasVisibleSeries = visibleSeries.revenue || visibleSeries.profit

  if (!hasSourceData) {
    return (
      <div
        className={[
          'flex min-h-[320px] items-center justify-center rounded-2xl border text-sm',
          isDark
            ? 'border-slate-800 bg-slate-950 text-slate-400'
            : 'border-slate-200 bg-slate-50 text-slate-500',
        ].join(' ')}
      >
        No performance data available.
      </div>
    )
  }

  if (!hasVisibleSeries) {
    return (
      <div
        className={[
          'flex items-center justify-center rounded-2xl border text-sm',
          isDark
            ? 'border-slate-800 bg-slate-950 text-slate-400'
            : 'border-slate-200 bg-slate-50 text-slate-500',
        ].join(' ')}
        style={{ height }}
      >
        Select at least one series from the legend.
      </div>
    )
  }

  return (
    <div className="w-full" style={{ height }}>
      <ResponsiveContainer width="100%" height="100%">
        <ComposedChart
          data={chartData}
          margin={{
            top: 14,
            right: 34,
            left: 22,
            bottom: 30,
          }}
        >
          <CartesianGrid
            vertical={false}
            strokeDasharray="4 4"
            stroke={isDark ? '#1E293B' : '#E2E8F0'}
          />

          <XAxis
            dataKey="label"
            axisLine={false}
            tickLine={false}
            interval={0}
            angle={0}
            textAnchor="middle"
            height={50}
            minTickGap={8}
            tickMargin={14}
            padding={{
              left: 28,
              right: 28,
            }}
            tickFormatter={formatXAxisLabel}
            tick={{
              fill: isDark ? '#94A3B8' : '#475569',
              fontSize: 12,
              fontWeight: 500,
            }}
          />

          <YAxis
            axisLine={false}
            tickLine={false}
            width={86}
            tickMargin={12}
            tickFormatter={(value) => formatCompactCurrency(Number(value))}
            tick={{
              fill: isDark ? '#94A3B8' : '#475569',
              fontSize: 12,
              fontWeight: 500,
            }}
          />

          <Tooltip
            cursor={{
              stroke: isDark ? '#334155' : '#CBD5E1',
              strokeWidth: 1,
            }}
            content={(props) => (
              <CustomTooltip
                active={props.active}
                payload={props.payload}
                label={String(props.label ?? '')}
                isDark={isDark}
              />
            )}
          />

          {visibleSeries.revenue ? (
            <Area
              type="linear"
              dataKey="revenue"
              name="Revenue"
              stroke={REVENUE_COLOR}
              strokeWidth={3}
              fill={REVENUE_COLOR}
              fillOpacity={0.12}
              dot={false}
              activeDot={{
                r: 5,
                strokeWidth: 3,
                stroke: isDark ? '#0F172A' : '#FFFFFF',
                fill: REVENUE_COLOR,
              }}
            />
          ) : null}

          {visibleSeries.profit ? (
            <Line
              type="linear"
              dataKey="profit"
              name="Profit"
              stroke={PROFIT_COLOR}
              strokeWidth={3}
              dot={{
                r: 3,
                strokeWidth: 2,
                stroke: isDark ? '#0F172A' : '#FFFFFF',
                fill: PROFIT_COLOR,
              }}
              activeDot={{
                r: 5,
                strokeWidth: 3,
                stroke: isDark ? '#0F172A' : '#FFFFFF',
                fill: PROFIT_COLOR,
              }}
            />
          ) : null}
        </ComposedChart>
      </ResponsiveContainer>
    </div>
  )
}