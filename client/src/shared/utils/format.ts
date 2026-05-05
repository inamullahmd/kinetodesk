import type { FinanceDailyTrendRow, FinanceTrendChartPoint, FinanceTrendGranularity } from "../../modules/finance/finance.types"

export function toNumber(value: number | string | null | undefined): number {
  return Number(value ?? 0)
}

export function formatCurrency(value: number | string | null | undefined): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 2,
  }).format(toNumber(value))
}

export function formatNumber(value: number | string | null | undefined): string {
  return new Intl.NumberFormat('en-US').format(toNumber(value))
}

export function formatPercent(value: number | null | undefined): string {
  if (value === null || value === undefined) return 'No comparison'
  const sign = value > 0 ? '+' : ''
  return `${sign}${value.toFixed(2)}%`
}

export function formatLabel(value: string | null | undefined): string {
  if (!value) return '-'

  return value
    .replace(/[_-]+/g, ' ')
    .toLowerCase()
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

export function formatCompactNumber(value: number | string | null | undefined): string {
  const num = toNumber(value)

  if (num >= 1_000_000_000) {
    return `$${(num / 1_000_000_000).toFixed(2)}B`
  }

  if (num >= 1_000_000) {
    return `$${(num / 1_000_000).toFixed(2)}M`
  }

  if (num >= 1_000) {
    return `$${(num / 1_000).toFixed(2)}K`
  }

  return num.toFixed(0)
}


export function formatPhoneNumber(value: string | null | undefined) {
  if (!value) return null

  const trimmed = value.trim()
  const digits = trimmed.replace(/\D/g, '')

  if (digits.length === 11 && digits.startsWith('1')) {
    return `+1 (${digits.slice(1, 4)}) ${digits.slice(4, 7)}-${digits.slice(7)}`
  }

  if (digits.length === 10) {
    return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`
  }

  return trimmed
}

export function formatEnumLabel(value: string | null | undefined) {
  if (!value) return '-'

  return value
    .split('_')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ')
}

export function formatDate(value: string | null | undefined) {
  if (!value) return '-'

  return new Date(`${value}T00:00:00`).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

function toDate(value: string) {
  return new Date(`${value}T00:00:00`)
}

function toIsoDate(date: Date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function getDaysBetween(startDate: string, endDate: string) {
  const start = toDate(startDate).getTime()
  const end = toDate(endDate).getTime()
  const dayMs = 1000 * 60 * 60 * 24

  return Math.max(1, Math.round((end - start) / dayMs) + 1)
}

export function getFinanceTrendGranularity(startDate: string, endDate: string): FinanceTrendGranularity {
  const dayCount = getDaysBetween(startDate, endDate)

  if (dayCount <= 62) return 'day'
  if (dayCount <= 186) return 'week'

  return 'month'
}

function getWeekStartIso(value: string) {
  const date = toDate(value)
  const day = date.getDay()
  const daysFromMonday = (day + 6) % 7

  date.setDate(date.getDate() - daysFromMonday)

  return toIsoDate(date)
}

function getMonthStartIso(value: string) {
  const date = toDate(value)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')

  return `${year}-${month}-01`
}

function formatTrendLabel(dateIso: string, granularity: FinanceTrendGranularity) {
  const date = toDate(dateIso)

  if (granularity === 'month') {
    return date.toLocaleDateString('en-US', {
      month: 'short',
      year: 'numeric',
    })
  }

  if (granularity === 'week') {
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
    })
  }

  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
  })
}

function getTrendBucketKey(row: FinanceDailyTrendRow, granularity: FinanceTrendGranularity) {
  if (granularity === 'month') return getMonthStartIso(row.date)
  if (granularity === 'week') return getWeekStartIso(row.date)

  return row.date
}

export function aggregateFinanceTrend(
  rows: FinanceDailyTrendRow[],
  granularity: FinanceTrendGranularity,
): FinanceTrendChartPoint[] {
  const buckets = new Map<string, FinanceTrendChartPoint>()

  rows.forEach((row) => {
    const bucketKey = getTrendBucketKey(row, granularity)

    const existing =
      buckets.get(bucketKey) ??
      {
        date: bucketKey,
        label: granularity === 'day' ? row.label : formatTrendLabel(bucketKey, granularity),
        revenue: 0,
        grossProfit: 0,
        payments: 0,
        orders: 0,
      }

    existing.revenue += numberValue(row.revenue)
    existing.grossProfit += numberValue(row.grossProfit)
    existing.payments += numberValue(row.payments)
    existing.orders += numberValue(row.orders)

    buckets.set(bucketKey, existing)
  })

  return Array.from(buckets.values()).sort((a, b) => a.date.localeCompare(b.date))
}

function numberValue(value: string | number) {
  if (typeof value === 'number') {
    return value
  }

  if (!value) {
    return 0
  }

  const parsed = Number(value)
  return Number.isNaN(parsed) ? 0 : parsed
}
