import {
  Bar,
  BarChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';

type TopProductsChartProps = {
  data: Array<{
    name: string;
    revenue: number;
  }>;
};

function formatCurrency(value: number | string) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    notation: 'compact',
    maximumFractionDigits: 1,
  }).format(Number(value));
}

export default function TopProductsChart({ data }: TopProductsChartProps) {
  return (
    <div className="rounded-[28px] border border-black/6 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] dark:border-white/6 dark:bg-[#151a24]">
      <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
        Product Performance
      </p>
      <h2 className="mt-1 text-[1.45rem] font-bold tracking-[-0.03em] text-slate-950 dark:text-white">
        Top products by revenue
      </h2>

      <div className="mt-4 h-[270px]">
        <ResponsiveContainer width="100%" height="100%">
          <BarChart data={data} layout="vertical" margin={{ top: 0, right: 16, left: 16, bottom: 0 }}>
            <CartesianGrid strokeDasharray="3 3" stroke="#d7dce5" horizontal={false} />
            <XAxis type="number" tick={{ fontSize: 11, fill: '#64748b' }} tickFormatter={formatCurrency} />
            <YAxis
              type="category"
              dataKey="name"
              tick={{ fontSize: 11, fill: '#64748b' }}
              width={110}
            />
            <Tooltip formatter={(value) => [formatCurrency(value as number), 'Revenue']} />
            <Bar dataKey="revenue" fill="#6d5df6" radius={[0, 8, 8, 0]} barSize={20} />
          </BarChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
}