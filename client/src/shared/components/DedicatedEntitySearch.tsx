import { Search, X } from 'lucide-react'
import { useState } from 'react'
import type { FormEvent } from 'react'
import { searchLookup } from '../api/lookup.api'
import type { LookupEntity, LookupResult } from '../types/lookup.types'

type DedicatedEntitySearchProps = {
  entity: LookupEntity
  placeholder: string
  onOpenResult: (result: LookupResult) => void
  label?: string
  className?: string
  variant?: 'light' | 'dark'
  helperText?: string
}

function formatMoney(value?: number | null) {
  if (value === null || value === undefined) return null

  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
  }).format(value)
}

function formatStatus(value?: string | null) {
  if (!value) return null

  return value
    .split('_')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ')
}

export default function DedicatedEntitySearch({
  entity,
  placeholder,
  onOpenResult,
  label = 'Direct lookup',
  className = '',
  variant = 'light',
  helperText = 'Ignores dashboard date filters and searches the full dataset.',
}: DedicatedEntitySearchProps) {
  const [query, setQuery] = useState('')
  const [results, setResults] = useState<LookupResult[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [searched, setSearched] = useState(false)

  const isDark = variant === 'dark'
  const canSearch = query.trim().length > 0

  const runSearch = async (event?: FormEvent<HTMLFormElement>) => {
    event?.preventDefault()

    if (!canSearch) {
      setResults([])
      setSearched(false)
      return
    }

    setLoading(true)
    setError(null)
    setSearched(true)

    try {
      const response = await searchLookup({
        entity,
        q: query.trim(),
        limit: 10,
      })

      setResults(response.results)
    } catch {
      setError('Unable to search right now.')
      setResults([])
    } finally {
      setLoading(false)
    }
  }

  const clear = () => {
    setQuery('')
    setResults([])
    setError(null)
    setSearched(false)
  }

  const sectionClass = [
    'rounded-2xl border p-4 shadow-sm',
    isDark
      ? 'border-slate-800 bg-slate-900/80 shadow-black/10'
      : 'border-slate-200 bg-white shadow-slate-200/60',
    className,
  ].join(' ')

  const labelClass = [
    'text-xs font-semibold uppercase tracking-wide',
    isDark ? 'text-slate-400' : 'text-slate-500',
  ].join(' ')

  const helperClass = [
    'text-sm',
    isDark ? 'text-slate-400' : 'text-slate-500',
  ].join(' ')

  const clearButtonClass = [
    'rounded-full p-1.5 transition',
    isDark
      ? 'text-slate-500 hover:bg-slate-800 hover:text-slate-200'
      : 'text-slate-400 hover:bg-slate-100 hover:text-slate-700',
  ].join(' ')

  const searchIconClass = [
    'pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2',
    isDark ? 'text-slate-500' : 'text-slate-400',
  ].join(' ')

  const inputClass = [
    'h-10 w-full rounded-xl border pl-9 pr-3 text-sm outline-none transition',
    isDark
      ? 'border-slate-800 bg-slate-950 text-slate-100 placeholder:text-slate-600 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10'
      : 'border-slate-200 bg-white text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-100',
  ].join(' ')

  const submitButtonClass = [
    'h-10 rounded-xl px-4 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50',
    isDark
      ? 'bg-blue-600 text-white hover:bg-blue-500'
      : 'bg-slate-950 text-white hover:bg-slate-800',
  ].join(' ')

  const emptyStateClass = [
    'rounded-xl px-3 py-2 text-sm',
    isDark
      ? 'bg-slate-950 text-slate-400'
      : 'bg-slate-50 text-slate-500',
  ].join(' ')

  const resultCardClass = [
    'flex items-center justify-between gap-3 rounded-xl border px-3 py-2',
    isDark
      ? 'border-slate-800 bg-slate-950/70'
      : 'border-slate-200 bg-white',
  ].join(' ')

  const resultTitleClass = [
    'truncate text-sm font-semibold',
    isDark ? 'text-white' : 'text-slate-950',
  ].join(' ')

  const resultMetaClass = [
    'truncate text-xs',
    isDark ? 'text-slate-400' : 'text-slate-500',
  ].join(' ')

  const statusClass = [
    'whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-medium',
    isDark
      ? 'bg-slate-800 text-slate-300'
      : 'bg-slate-100 text-slate-600',
  ].join(' ')

  const viewButtonClass = [
    'shrink-0 rounded-lg border px-3 py-1.5 text-xs font-semibold transition',
    isDark
      ? 'border-slate-700 text-slate-200 hover:border-slate-600 hover:bg-slate-900'
      : 'border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50',
  ].join(' ')

  return (
    <section className={sectionClass}>
      <div className="mb-2 flex items-center justify-between gap-3">
        <div>
          <p className={labelClass}>{label}</p>
          <p className={helperClass}>{helperText}</p>
        </div>

        {query ? (
          <button
            type="button"
            onClick={clear}
            className={clearButtonClass}
            aria-label="Clear direct lookup"
          >
            <X className="h-4 w-4" />
          </button>
        ) : null}
      </div>

      <form onSubmit={runSearch} className="flex gap-2">
        <div className="relative min-w-0 flex-1">
          <Search className={searchIconClass} />

          <input
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder={placeholder}
            className={inputClass}
          />
        </div>

        <button
          type="submit"
          disabled={!canSearch || loading}
          className={submitButtonClass}
        >
          {loading ? 'Searching…' : 'Search'}
        </button>
      </form>

      {error ? <p className="mt-3 text-sm text-rose-500">{error}</p> : null}

      {searched && !loading && !error ? (
        <div className="mt-3 space-y-2">
          {results.length === 0 ? (
            <p className={emptyStateClass}>No matches found.</p>
          ) : (
            results.map((result) => {
              const amount = formatMoney(result.amount)
              const status = formatStatus(result.status)

              return (
                <div
                  key={`${result.entity}-${result.id}`}
                  className={resultCardClass}
                >
                  <div className="min-w-0">
                    <div className="flex min-w-0 items-center gap-2">
                      <p className={resultTitleClass}>{result.title}</p>

                      {status ? (
                        <span className={statusClass}>{status}</span>
                      ) : null}
                    </div>

                    <p className={resultMetaClass}>
                      {[result.subtitle, result.date, amount, result.meta]
                        .filter(Boolean)
                        .join(' · ')}
                    </p>
                  </div>

                  <button
                    type="button"
                    onClick={() => onOpenResult(result)}
                    className={viewButtonClass}
                  >
                    View
                  </button>
                </div>
              )
            })
          )}
        </div>
      ) : null}
    </section>
  )
}