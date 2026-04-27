import type { TopEmployee } from '../../types/dashboard'
import { formatCurrency, formatNumber, toNumber } from '../../utils/format'

type TopEmployeesListProps = {
  items: TopEmployee[]
}

export default function TopEmployeesList({ items }: TopEmployeesListProps) {
  if (!items.length) {
    return (
      <div className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
        No employee sales found.
      </div>
    )
  }

  const maxSales = Math.max(...items.map((item) => toNumber(item.total_sales)), 0)

  return (
    <div className="space-y-4">
      {items.map((item, index) => {
        const sales = toNumber(item.total_sales)
        const orders = toNumber(item.total_orders)
        const width = maxSales > 0 ? (sales / maxSales) * 100 : 0

        return (
          <div
            key={item.employee_id}
            className="rounded-2xl border border-slate-200 bg-white px-4 py-4"
          >
            <div className="flex items-start justify-between gap-4">
              <div className="min-w-0 flex-1">
                <div className="flex items-center gap-3">
                  <span className="inline-flex h-7 min-w-7 items-center justify-center rounded-full bg-slate-100 px-2 text-xs font-semibold text-slate-600">
                    #{index + 1}
                  </span>

                  <div className="min-w-0">
                    <h4 className="truncate text-sm font-semibold text-slate-900">
                      {item.employee_name}
                    </h4>
                    <p className="mt-0.5 text-xs text-slate-500">
                      {formatNumber(orders)} orders
                    </p>
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
                  {formatCurrency(sales)}
                </div>
              </div>
            </div>
          </div>
        )
      })}
    </div>
  )
}