import type { RecentSale } from '@/types/overview';

type RecentSalesTableProps = {
  items: RecentSale[];
};

function statusClasses(status: string) {
  switch (status) {
    case 'completed':
      return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300';
    case 'pending':
      return 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300';
    case 'refunded':
      return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
    case 'cancelled':
      return 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300';
    default:
      return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
  }
}

export default function RecentSalesTable({ items }: RecentSalesTableProps) {
  return (
    <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div className="mb-4">
        <p className="text-xs font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
          Sales Feed
        </p>
        <h2 className="mt-2 text-2xl font-bold tracking-[-0.02em] text-slate-900 dark:text-white">
          Recent sales
        </h2>
      </div>

      <div className="overflow-x-auto">
        <table className="min-w-full text-sm">
          <thead>
            <tr className="border-b border-slate-200 dark:border-slate-800">
              <th className="py-3 pr-4 text-left font-semibold text-slate-500 dark:text-slate-400">
                Order
              </th>
              <th className="py-3 pr-4 text-left font-semibold text-slate-500 dark:text-slate-400">
                Customer
              </th>
              <th className="py-3 pr-4 text-left font-semibold text-slate-500 dark:text-slate-400">
                Product
              </th>
              <th className="py-3 pr-4 text-left font-semibold text-slate-500 dark:text-slate-400">
                Qty
              </th>
              <th className="py-3 pr-4 text-left font-semibold text-slate-500 dark:text-slate-400">
                Total
              </th>
              <th className="py-3 text-left font-semibold text-slate-500 dark:text-slate-400">
                Status
              </th>
            </tr>
          </thead>
          <tbody>
            {items.map((sale) => (
              <tr key={sale.order_number} className="border-b border-slate-100 dark:border-slate-800/70">
                <td className="py-4 pr-4 font-normal text-slate-800 dark:text-slate-100">
                  {sale.order_number}
                </td>
                <td className="py-4 pr-4 text-slate-700 dark:text-slate-300">{sale.customer_name}</td>
                <td className="py-4 pr-4 text-slate-500 dark:text-slate-400">{sale.product_name}</td>
                <td className="py-4 pr-4 text-slate-700 dark:text-slate-300">{sale.quantity}</td>
                <td className="py-4 pr-4 font-semibold text-slate-800 dark:text-slate-100">
                  ${Number(sale.total_amount).toFixed(2)}
                </td>
                <td className="py-4">
                  <span className={`rounded-full px-2.5 py-1 text-xs font-bold ${statusClasses(sale.status)}`}>
                    {sale.status}
                  </span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}