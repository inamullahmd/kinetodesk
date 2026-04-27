import type { TopSellingProduct } from '../../types/dashboard'
import { formatNumber, toNumber } from '../../utils/format'

type TopProductsListProps = {
  items: TopSellingProduct[]
}

export default function TopProductsList({ items }: TopProductsListProps) {
  if (!items.length) {
    return (
      <div className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
        No product sales found.
      </div>
    )
  }

  const maxQty = Math.max(...items.map((item) => toNumber(item.total_quantity)), 0)

  return (
    <div className="space-y-4">
      {items.map((item, index) => {
        const qty = toNumber(item.total_quantity)
        const width = maxQty > 0 ? (qty / maxQty) * 100 : 0

        return (
          <div
            key={item.product_id}
            className="rounded-2xl border border-slate-200 bg-white px-4 py-4"
          >
            <div className="flex items-start justify-between gap-4">
              <div className="min-w-0 flex-1">
                <div className="flex items-center gap-3">
                  <span className="inline-flex h-7 min-w-7 items-center justify-center rounded-full bg-slate-100 px-2 text-xs font-semibold text-slate-600">
                    #{index + 1}
                  </span>

                  <div className="min-w-0">
                    <h4 className="line-clamp-2 text-sm font-semibold text-slate-900">
                      {item.product_name}
                    </h4>
                  </div>
                </div>

                <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                  <div
                    className="h-full rounded-full bg-[#7C3AED]"
                    style={{ width: `${width}%` }}
                  />
                </div>
              </div>

              <div className="shrink-0 text-right">
                <div className="text-sm font-semibold text-slate-900">
                  {formatNumber(qty)}
                </div>
                <div className="mt-0.5 text-xs text-slate-500">
                  qty sold
                </div>
              </div>
            </div>
          </div>
        )
      })}
    </div>
  )
}