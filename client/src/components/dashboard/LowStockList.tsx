import type { LowStockProduct } from '../../types/dashboard'
import { AlertTriangle, ArrowRight, PackageX } from 'lucide-react'

type LowStockListProps = {
  items: LowStockProduct[]
}

function toneClasses(tone: LowStockProduct['stock_status']['tone']) {
  switch (tone) {
    case 'danger':
      return {
        badge: 'bg-rose-50 text-rose-700 ring-rose-200',
        icon: 'text-rose-600',
        stock: 'text-rose-700',
        row: 'border-rose-100',
      }
    case 'warning':
      return {
        badge: 'bg-orange-50 text-orange-700 ring-orange-200',
        icon: 'text-orange-600',
        stock: 'text-orange-700',
        row: 'border-orange-100',
      }
    case 'caution':
      return {
        badge: 'bg-amber-50 text-amber-700 ring-amber-200',
        icon: 'text-amber-600',
        stock: 'text-amber-700',
        row: 'border-amber-100',
      }
    default:
      return {
        badge: 'bg-sky-50 text-sky-700 ring-sky-200',
        icon: 'text-sky-600',
        stock: 'text-sky-700',
        row: 'border-sky-100',
      }
  }
}

export default function LowStockList({ items }: LowStockListProps) {
  if (!items.length) {
    return (
      <div className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-5 text-center text-sm text-slate-500">
        No low stock alerts.
      </div>
    )
  }

  return (
    <div className="space-y-2.5">
      {items.map((item) => {
        const styles = toneClasses(item.stock_status.tone)
        const isOutOfStock = item.stock_qty === 0

        return (
          <div
            key={item.id}
            className={`rounded-xl border bg-white px-4 py-2.5 ${styles.row}`}
          >
            <div className="flex items-start justify-between gap-3">
              <div className="min-w-0 flex-1">
                <div className="flex items-start gap-2.5">
                  <div className="mt-0.5 shrink-0">
                    {isOutOfStock ? (
                      <PackageX size={16} className={styles.icon} />
                    ) : (
                      <AlertTriangle size={16} className={styles.icon} />
                    )}
                  </div>

                  <div className="min-w-0">
                    <h4 className="line-clamp-1 text-sm font-medium text-slate-900">
                      {item.title}
                    </h4>

                    <p className="mt-0.5 text-xs text-slate-500">
                      {item.stock_status.action}
                    </p>
                  </div>
                </div>
              </div>

              <div className="shrink-0 text-right">
                <div
                  className={`inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ${styles.badge}`}
                >
                  {item.stock_status.label}
                </div>

                <div className={`mt-1 text-xs font-semibold ${styles.stock}`}>
                  Stock: {item.stock_qty}
                </div>
              </div>
            </div>
          </div>
        )
      })}

      <div className="pt-1">
        <button
          type="button"
          className="inline-flex items-center gap-2 text-sm font-medium text-[#6C3BFF] hover:text-[#5B2EFF]"
        >
          View inventory alerts
          <ArrowRight size={15} />
        </button>
      </div>
    </div>
  )
}