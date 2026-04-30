import { ExternalLink } from 'lucide-react'
import type { LowStockProduct } from '../../types/dashboard'

type LowStockListProps = {
  items: LowStockProduct[]
  variant?: 'light' | 'dark'
}

function formatLastSale(item: LowStockProduct) {
  if (typeof item.days_out_of_stock === 'number') {
    return `${item.days_out_of_stock} days since last sale`
  }

  if (item.last_sold_at) {
    return `Last sold ${item.last_sold_at}`
  }

  return 'No recent sale'
}

function getStatus(item: LowStockProduct) {
  if (item.stock_qty === 0) {
    return {
      label: 'Out of stock',
      dotClass: 'bg-rose-500',
      textClassLight: 'text-rose-600',
      textClassDark: 'text-rose-300',
    }
  }

  if (item.stock_qty <= 3) {
    return {
      label: 'Critical',
      dotClass: 'bg-amber-500',
      textClassLight: 'text-amber-700',
      textClassDark: 'text-amber-300',
    }
  }

  return {
    label: 'Low stock',
    dotClass: 'bg-blue-500',
    textClassLight: 'text-blue-700',
    textClassDark: 'text-blue-300',
  }
}

export default function LowStockList({
  items,
  variant = 'light',
}: LowStockListProps) {
  const isDark = variant === 'dark'
  const visibleItems = items.slice(0, 5)

  if (!visibleItems.length) {
    return (
      <div
        className={[
          'rounded-2xl border px-4 py-8 text-center text-sm',
          isDark
            ? 'border-slate-800 bg-slate-950 text-slate-400'
            : 'border-slate-200 bg-slate-50 text-slate-500',
        ].join(' ')}
      >
        No low stock products found.
      </div>
    )
  }

  return (
    <div>
      <div className="space-y-3.5">
        {visibleItems.map((item) => {
          const status = getStatus(item)

          return (
            <div
              key={item.id}
              className={[
                'rounded-2xl border px-4 py-4',
                isDark
                  ? 'border-slate-800 bg-slate-950/40'
                  : 'border-slate-200 bg-white',
              ].join(' ')}
            >
              <div className="grid grid-cols-[minmax(0,1fr)_auto] gap-4">
                <div className="min-w-0">
                  <div className="flex min-w-0 items-center gap-2">
                    <span
                      className={[
                        'h-2.5 w-2.5 shrink-0 rounded-full',
                        status.dotClass,
                      ].join(' ')}
                    />

                    <div
                      className={[
                        'truncate text-sm font-semibold',
                        isDark ? 'text-white' : 'text-slate-950',
                      ].join(' ')}
                    >
                      {item.title}
                    </div>
                  </div>

                  <div
                    className={[
                      'mt-1 truncate text-xs',
                      isDark ? 'text-slate-400' : 'text-slate-500',
                    ].join(' ')}
                  >
                    {item.internal_sku || 'No SKU available'}
                  </div>

                  <div
                    className={[
                      'mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs',
                      isDark ? 'text-slate-400' : 'text-slate-500',
                    ].join(' ')}
                  >
                    <span
                      className={[
                        'font-semibold',
                        isDark ? status.textClassDark : status.textClassLight,
                      ].join(' ')}
                    >
                      {status.label}
                    </span>

                    <span className={isDark ? 'text-slate-700' : 'text-slate-300'}>
                      /
                    </span>

                    <span className="font-data">
                      {formatLastSale(item)}
                    </span>
                  </div>
                </div>

                <div className="flex shrink-0 flex-col items-end justify-center">
                  <div
                    className={[
                      'font-data text-lg font-bold leading-none',
                      item.stock_qty === 0
                        ? isDark
                          ? 'text-rose-300'
                          : 'text-rose-600'
                        : isDark
                          ? 'text-white'
                          : 'text-slate-950',
                    ].join(' ')}
                  >
                    {item.stock_qty}
                  </div>

                  <div
                    className={[
                      'mt-1 text-[10px] font-semibold uppercase tracking-[0.12em]',
                      isDark ? 'text-slate-500' : 'text-slate-400',
                    ].join(' ')}
                  >
                    units
                  </div>
                </div>
              </div>
            </div>
          )
        })}
      </div>

      <button
        type="button"
        className={[
          'mt-4 inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold transition',
          isDark
            ? 'border-slate-700 bg-slate-950 text-slate-200 hover:border-slate-600 hover:bg-slate-900'
            : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50',
        ].join(' ')}
      >
        Review inventory alerts
        <ExternalLink size={14} />
      </button>
    </div>
  )
}