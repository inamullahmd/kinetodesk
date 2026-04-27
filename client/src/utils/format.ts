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

  return num.toFixed(2)
}