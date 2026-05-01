import { useEffect, useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import {
  Bar,
  BarChart,
  CartesianGrid,
  Legend,
  Line,
  LineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { useOutletContext } from 'react-router-dom'
import SectionCard from '../../shared/components/SectionCard'
import StatCard from '../../shared/components/StatCard'
import { getFinanceOverview } from './finance.api'
import type { DashboardOutletContext } from '../../app/DashboardLayout'
import type {
  CommissionPayoutRow,
  FinanceOverviewResponse,
  OpenReceivableRow,
  PaymentMethodBreakdownRow,
  RecentPaymentRow,
  RecentRefundRow,
} from './finance.types'
import {
  formatCompactNumber,
  formatCurrency,
  formatNumber,
} from '../../shared/utils/format'

const DEFAULT_DATE_RANGE = {
  startDate: '2026-03-01',
  endDate: '2026-03-31',
}

function formatDate(value: string | null | undefined) {
  if (!value) return '-'

  return new Date(`${value}T00:00:00`).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

function formatEnumLabel(value: string | null | undefined) {
  if (!value) return '-'

  return value
    .split('_')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ')
}

function numberValue(value: number | string | null | undefined) {
  return Number(value ?? 0)
}

function axisCurrency(value: number) {
  return formatCompactNumber(value)
}

function tooltipCurrency(value: number | string) {
  return formatCurrency(Number(value ?? 0))
}

function inputClass(isDark: boolean) {
  return [
    'h-10 rounded-xl border px-3 text-sm outline-none transition',
    isDark
      ? 'border-slate-800 bg-slate-950 text-slate-200 placeholder:text-slate-600 focus:border-blue-500'
      : 'border-slate-200 bg-white text-slate-700 placeholder:text-slate-400 focus:border-blue-500',
  ].join(' ')
}

function StatusPill({
  status,
  variant,
}: {
  status: string
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'
  const normalized = status.toLowerCase()

  const className =
    normalized === 'paid' || normalized === 'delivered' || normalized === 'closed'
      ? isDark
        ? 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/20'
        : 'bg-emerald-50 text-emerald-700 ring-emerald-200'
      : normalized === 'refunded' || normalized === 'returned'
        ? isDark
          ? 'bg-violet-500/10 text-violet-300 ring-violet-500/20'
          : 'bg-violet-50 text-violet-700 ring-violet-200'
        : normalized === 'unpaid' || normalized === 'pending'
          ? isDark
            ? 'bg-amber-500/10 text-amber-300 ring-amber-500/20'
            : 'bg-amber-50 text-amber-700 ring-amber-200'
          : isDark
            ? 'bg-slate-800 text-slate-300 ring-slate-700'
            : 'bg-slate-100 text-slate-600 ring-slate-200'

  return (
    <span
      className={[
        'inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',
        className,
      ].join(' ')}
    >
      {formatEnumLabel(status)}
    </span>
  )
}

function FinanceChartCard({
  title,
  description,
  children,
  variant,
}: {
  title: string
  description: string
  children: ReactNode
  variant: 'light' | 'dark'
}) {
  return (
    <SectionCard title={title} description={description} variant={variant}>
      <div className="h-80">{children}</div>
    </SectionCard>
  )
}

function EmptyState({
  text,
  variant,
}: {
  text: string
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <div
      className={[
        'rounded-2xl border p-6 text-center text-sm',
        isDark
          ? 'border-slate-800 bg-slate-950/40 text-slate-400'
          : 'border-slate-200 bg-slate-50 text-slate-500',
      ].join(' ')}
    >
      {text}
    </div>
  )
}

function MoneyListCard({
  title,
  description,
  rows,
  variant,
}: {
  title: string
  description: string
  rows: Array<{
    id: string | number
    title: string
    subtitle?: string
    value: number | string
    badge?: ReactNode
  }>
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <SectionCard title={title} description={description} variant={variant}>
      {rows.length ? (
        <div className="space-y-3">
          {rows.map((row) => (
            <div
              key={row.id}
              className={[
                'flex items-center justify-between gap-4 rounded-2xl border px-4 py-3',
                isDark
                  ? 'border-slate-800 bg-slate-950/40'
                  : 'border-slate-200 bg-white',
              ].join(' ')}
            >
              <div className="min-w-0">
                <div
                  className={`truncate text-sm font-semibold ${
                    isDark ? 'text-white' : 'text-slate-950'
                  }`}
                >
                  {row.title}
                </div>

                {row.subtitle ? (
                  <div
                    className={`mt-1 truncate text-xs ${
                      isDark ? 'text-slate-500' : 'text-slate-400'
                    }`}
                  >
                    {row.subtitle}
                  </div>
                ) : null}

                {row.badge ? <div className="mt-2">{row.badge}</div> : null}
              </div>

              <div
                className={`shrink-0 text-right font-data text-sm font-semibold ${
                  isDark ? 'text-white' : 'text-slate-950'
                }`}
              >
                {formatCurrency(row.value)}
              </div>
            </div>
          ))}
        </div>
      ) : (
        <EmptyState text="No data found for the selected period." variant={variant} />
      )}
    </SectionCard>
  )
}

function DataTable({
  headings,
  rows,
  emptyText,
  variant,
}: {
  headings: string[]
  rows: ReactNode
  emptyText: string
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <div className="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
      <table className="w-full table-fixed divide-y divide-slate-200 dark:divide-slate-800">
        <thead className={isDark ? 'bg-slate-950' : 'bg-slate-50'}>
          <tr>
            {headings.map((heading) => (
              <th
                key={heading}
                className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
              >
                {heading}
              </th>
            ))}
          </tr>
        </thead>

        <tbody
          className={`divide-y text-sm ${
            isDark
              ? 'divide-slate-800 bg-slate-900 text-slate-300'
              : 'divide-slate-100 bg-white text-slate-700'
          }`}
        >
          {rows ?? (
            <tr>
              <td colSpan={headings.length} className="px-4 py-8 text-center text-sm text-slate-500">
                {emptyText}
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  )
}

export default function FinanceOverviewPage() {
  const { setHeaderRange, setHeaderDateRangeControl, theme } =
    useOutletContext<DashboardOutletContext>()

  const [startDate, setStartDate] = useState(DEFAULT_DATE_RANGE.startDate)
  const [endDate, setEndDate] = useState(DEFAULT_DATE_RANGE.endDate)
  const [data, setData] = useState<FinanceOverviewResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const isDark = theme === 'dark'
  const chartText = isDark ? '#94a3b8' : '#64748b'
  const gridColor = isDark ? '#1e293b' : '#e2e8f0'

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

    async function loadFinance() {
      try {
        setLoading(true)
        setError(null)

        const response = await getFinanceOverview({
          startDate,
          endDate,
        })

        if (!active) return
        setData(response)
      } catch {
        if (!active) return
        setError('Failed to load finance overview.')
      } finally {
        if (active) setLoading(false)
      }
    }

    loadFinance()

    return () => {
      active = false
    }
  }, [endDate, startDate])

  const paymentStatusChart = useMemo(
    () =>
      data?.paymentStatusBreakdown.map((row) => ({
        label: formatEnumLabel(row.status),
        value: numberValue(row.totalValue),
        orders: row.orderCount,
      })) ?? [],
    [data],
  )

  if (loading) {
    return (
      <SectionCard title="Finance" description="Loading financial data..." variant={theme}>
        <div className="py-14 text-center text-sm text-slate-500">
          Loading finance overview...
        </div>
      </SectionCard>
    )
  }

  if (error || !data) {
    return (
      <SectionCard title="Finance" description="Finance overview could not be loaded." variant={theme}>
        <div className="rounded-2xl border border-rose-200 bg-rose-50 p-8 text-center text-sm text-rose-700">
          {error ?? 'No finance data available.'}
        </div>
      </SectionCard>
    )
  }

  const summary = data.summary
  const totalRefunds = numberValue(summary.totalRefunds)

  return (
    <div className="space-y-6">
      <div className="flex flex-col justify-between gap-4 xl:flex-row xl:items-end">
        <div>
          <h1
            className={`text-3xl font-semibold tracking-tight ${
              isDark ? 'text-white' : 'text-slate-950'
            }`}
          >
            Finance Overview
          </h1>
          <p className={`mt-1 text-sm ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
            Revenue, profit, payments, refunds, receivables, and commissions.
          </p>
        </div>

        <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
          <label className="flex flex-col gap-1">
            <span className={`text-xs font-semibold ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
              Start
            </span>
            <input
              type="date"
              value={startDate}
              onChange={(event) => setStartDate(event.target.value)}
              className={inputClass(isDark)}
            />
          </label>

          <label className="flex flex-col gap-1">
            <span className={`text-xs font-semibold ${isDark ? 'text-slate-400' : 'text-slate-500'}`}>
              End
            </span>
            <input
              type="date"
              value={endDate}
              onChange={(event) => setEndDate(event.target.value)}
              className={inputClass(isDark)}
            />
          </label>
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
        <StatCard
          title="Revenue"
          value={formatCompactNumber(summary.revenue)}
          secondaryValue={formatCurrency(summary.revenue)}
          helperText="Non-cancelled sales"
          variant={theme}
          compact
          monoSecondary
        />
        <StatCard
          title="Gross Profit"
          value={formatCompactNumber(summary.grossProfit)}
          secondaryValue={formatCurrency(summary.grossProfit)}
          helperText={`${summary.grossMargin}% margin`}
          variant={theme}
          compact
          monoSecondary
        />
        <StatCard
          title="Payments"
          value={formatCompactNumber(summary.paymentsCollected)}
          secondaryValue={formatCurrency(summary.paymentsCollected)}
          helperText="Collected payments"
          variant={theme}
          compact
          monoSecondary
        />
        <StatCard
          title="Receivables"
          value={formatCompactNumber(summary.pendingReceivables)}
          secondaryValue={formatCurrency(summary.pendingReceivables)}
          helperText="Unpaid sales orders"
          variant={theme}
          compact
          monoSecondary
        />
        <StatCard
          title="Refunds"
          value={formatCompactNumber(totalRefunds)}
          secondaryValue={formatCurrency(totalRefunds)}
          helperText={`${formatNumber(summary.refundRecordCount)} refund records`}
          variant={theme}
          compact
          monoSecondary
        />
        <StatCard
          title="Pending Commission"
          value={formatCompactNumber(summary.pendingCommission)}
          secondaryValue={formatCurrency(summary.pendingCommission)}
          helperText="Unpaid payouts"
          variant={theme}
          compact
          monoSecondary
        />
      </div>

      <div className="grid items-start gap-6 xl:grid-cols-[1.35fr_0.9fr]">
        <FinanceChartCard
          title="Financial Trend"
          description="Daily revenue, gross profit, and payments collected."
          variant={theme}
        >
          <ResponsiveContainer width="100%" height="100%">
            <LineChart
              data={data.dailyTrend}
              margin={{ top: 12, right: 22, bottom: 8, left: 0 }}
            >
              <CartesianGrid strokeDasharray="3 3" stroke={gridColor} />
              <XAxis
                dataKey="label"
                tick={{ fill: chartText, fontSize: 11 }}
                minTickGap={14}
              />
              <YAxis
                tick={{ fill: chartText, fontSize: 11 }}
                tickFormatter={(value) => axisCurrency(Number(value))}
                width={72}
              />
              <Tooltip formatter={(value) => tooltipCurrency(value as string | number)} />
              <Legend />
              <Line
                type="monotone"
                dataKey="revenue"
                name="Revenue"
                stroke="#2563eb"
                strokeWidth={2.5}
                dot={false}
              />
              <Line
                type="monotone"
                dataKey="grossProfit"
                name="Gross Profit"
                stroke="#7c3aed"
                strokeWidth={2.5}
                dot={false}
              />
              <Line
                type="monotone"
                dataKey="payments"
                name="Payments"
                stroke="#10b981"
                strokeWidth={2.5}
                dot={false}
              />
            </LineChart>
          </ResponsiveContainer>
        </FinanceChartCard>

        <FinanceChartCard
          title="Payment Status"
          description="Open, paid, and refunded sales value."
          variant={theme}
        >
          <ResponsiveContainer width="100%" height="100%">
            <BarChart
              data={paymentStatusChart}
              margin={{ top: 12, right: 22, bottom: 8, left: 0 }}
            >
              <CartesianGrid strokeDasharray="3 3" stroke={gridColor} />
              <XAxis dataKey="label" tick={{ fill: chartText, fontSize: 11 }} />
              <YAxis
                tick={{ fill: chartText, fontSize: 11 }}
                tickFormatter={(value) => axisCurrency(Number(value))}
                width={72}
              />
              <Tooltip formatter={(value) => tooltipCurrency(value as string | number)} />
              <Legend />
              <Bar
                dataKey="value"
                name="Value"
                fill="#2563eb"
                radius={[8, 8, 0, 0]}
              />
            </BarChart>
          </ResponsiveContainer>
        </FinanceChartCard>
      </div>

      <div className="grid items-start gap-6 xl:grid-cols-2">
        <PaymentMethodsCard rows={data.paymentMethodBreakdown} variant={theme} />
        <OpenReceivablesCard rows={data.openReceivables} variant={theme} />
      </div>

      <RecentPaymentsCard rows={data.recentPayments} variant={theme} />

      {data.recentRefunds.length ? (
        <RecentRefundsCard rows={data.recentRefunds} variant={theme} />
      ) : null}

      {data.commissionPayouts.length ? (
        <CommissionsCard rows={data.commissionPayouts} variant={theme} />
      ) : null}
    </div>
  )
}

function PaymentMethodsCard({
  rows,
  variant,
}: {
  rows: PaymentMethodBreakdownRow[]
  variant: 'light' | 'dark'
}) {
  return (
    <MoneyListCard
      title="Payment Methods"
      description="Collected payment mix by method."
      variant={variant}
      rows={rows.map((row) => ({
        id: row.method,
        title: formatEnumLabel(row.method),
        subtitle: `${formatNumber(row.paymentCount)} payments`,
        value: row.totalAmount,
      }))}
    />
  )
}

function OpenReceivablesCard({
  rows,
  variant,
}: {
  rows: OpenReceivableRow[]
  variant: 'light' | 'dark'
}) {
  return (
    <MoneyListCard
      title="Open Receivables"
      description="Largest unpaid sales orders in the selected period."
      variant={variant}
      rows={rows.slice(0, 6).map((row) => ({
        id: row.id,
        title: row.customerName,
        subtitle: `${row.salesOrderNumber} / ${formatDate(row.orderedAt)} / ${formatEnumLabel(row.channel)}`,
        value: row.amountDue,
      }))}
    />
  )
}

function RecentPaymentsCard({
  rows,
  variant,
}: {
  rows: RecentPaymentRow[]
  variant: 'light' | 'dark'
}) {
  return (
    <SectionCard
      title="Recent Payments"
      description="Latest payments collected in the selected period."
      variant={variant}
    >
      <DataTable
        headings={['Date', 'SO', 'Customer', 'Method', 'Amount']}
        emptyText="No recent payments found."
        variant={variant}
        rows={
          rows.length
            ? rows.map((row) => (
                <tr key={row.id}>
                  <td className="truncate px-4 py-3">{formatDate(row.paidAt)}</td>
                  <td className="truncate px-4 py-3 font-data font-semibold">
                    {row.salesOrderNumber}
                  </td>
                  <td className="truncate px-4 py-3">{row.customerName}</td>
                  <td className="truncate px-4 py-3">{formatEnumLabel(row.method)}</td>
                  <td className="truncate px-4 py-3 text-right font-data font-semibold">
                    {formatCurrency(row.amount)}
                  </td>
                </tr>
              ))
            : null
        }
      />
    </SectionCard>
  )
}

function RecentRefundsCard({
  rows,
  variant,
}: {
  rows: RecentRefundRow[]
  variant: 'light' | 'dark'
}) {
  return (
    <SectionCard
      title="Recent Refunds"
      description="Return refund activity in the selected period."
      variant={variant}
    >
      <DataTable
        headings={['Date', 'Return', 'Customer', 'Status', 'Refund']}
        emptyText="No refunds found."
        variant={variant}
        rows={
          rows.length
            ? rows.map((row) => (
                <tr key={row.id}>
                  <td className="truncate px-4 py-3">{formatDate(row.createdAt)}</td>
                  <td className="truncate px-4 py-3 font-data font-semibold">
                    {row.returnNumber}
                  </td>
                  <td className="truncate px-4 py-3">{row.customerName}</td>
                  <td className="px-4 py-3">
                    <StatusPill status={row.status} variant={variant} />
                  </td>
                  <td className="truncate px-4 py-3 text-right font-data font-semibold">
                    {formatCurrency(row.refundAmount)}
                  </td>
                </tr>
              ))
            : null
        }
      />
    </SectionCard>
  )
}

function CommissionsCard({
  rows,
  variant,
}: {
  rows: CommissionPayoutRow[]
  variant: 'light' | 'dark'
}) {
  return (
    <SectionCard
      title="Commission Payouts"
      description="Pending and paid commission payouts overlapping the selected period."
      variant={variant}
    >
      <DataTable
        headings={['Employee', 'Period', 'Status', 'Paid At', 'Commission']}
        emptyText="No commission payouts found."
        variant={variant}
        rows={
          rows.length
            ? rows.map((row) => (
                <tr key={row.id}>
                  <td className="truncate px-4 py-3">
                    <p className="truncate font-semibold">{row.employeeName}</p>
                    <p className="truncate text-xs text-slate-500">{row.employeeNumber}</p>
                  </td>
                  <td className="truncate px-4 py-3">
                    {formatDate(row.periodStart)} - {formatDate(row.periodEnd)}
                  </td>
                  <td className="px-4 py-3">
                    <StatusPill status={row.status} variant={variant} />
                  </td>
                  <td className="truncate px-4 py-3">{formatDate(row.paidAt)}</td>
                  <td className="truncate px-4 py-3 text-right font-data font-semibold">
                    {formatCurrency(row.totalCommission)}
                  </td>
                </tr>
              ))
            : null
        }
      />
    </SectionCard>
  )
}