import type { LowStockItem } from '@/types/overview';

type LowStockTableProps = {
  items: LowStockItem[];
};

export default function LowStockTable({ items }: LowStockTableProps) {
  return (
    <div className="rounded-[28px] border border-black/6 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] dark:border-white/6 dark:bg-[#11141d]">
      <div className="mb-4">
        <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
          Inventory Risk
        </p>
        <h2 className="mt-1 text-[1.6rem] font-bold tracking-[-0.03em] text-slate-950 dark:text-white">
          Low stock items
        </h2>
      </div>

      <div className="overflow-x-auto">
        <table className="min-w-full text-[13px]">
          <thead>
            <tr className="border-b border-slate-200 dark:border-white/8">
              <th className="py-3 pr-4 text-left font-medium text-slate-500 dark:text-slate-400">
                Product
              </th>
              <th className="py-3 pr-4 text-left font-medium text-slate-500 dark:text-slate-400">
                SKU
              </th>
              <th className="py-3 pr-4 text-left font-medium text-slate-500 dark:text-slate-400">
                Stock
              </th>
              <th className="py-3 text-left font-medium text-slate-500 dark:text-slate-400">
                Reorder
              </th>
            </tr>
          </thead>
          <tbody>
            {items.map((item) => (
              <tr key={item.product_id} className="border-b border-slate-100 dark:border-white/6">
                <td className="py-3.5 pr-4 text-slate-800 dark:text-slate-100">{item.product_name}</td>
                <td className="py-3.5 pr-4 text-slate-500 dark:text-slate-400">{item.sku}</td>
                <td className="py-3.5 pr-4">
                  <span className="rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-semibold text-rose-600 dark:bg-rose-950/40 dark:text-rose-300">
                    {item.stock_on_hand}
                  </span>
                </td>
                <td className="py-3.5 text-slate-700 dark:text-slate-300">{item.reorder_level}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}