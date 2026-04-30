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