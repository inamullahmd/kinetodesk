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
  const isDark = document.documentElement.classList.contains('dark');

  const gridColor = isDark ? '#2a3140' : '#d7dce5';
  const axisColor = isDark ? '#94a3b8' : '#64748b';
  const tooltipBg = isDark ? '#11141d' : '#ffffff';
  const tooltipBorder = isDark ? '#273042' : '#dbe1ea';
  const barColor = isDark ? '#8b5cf6' : '#6d5df6';

  return (
    <div className="rounded-[28px] border border-black/6 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] dark:border-white/6 dark:bg-[#151a24]">
      <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
        Product Performance
      </p>
      <h2 className="mt-1 text-[1.35rem] font-bold tracking-[-0.03em] text-slate-950 dark:text-white">
        Top products by revenue
      </h2>

      <div className="mt-4 h-[270px]">
        <ResponsiveContainer width="100%" height="100%">
          <BarChart data={data} layout="vertical" margin={{ top: 0, right: 16, left: 16, bottom: 0 }}>
            <CartesianGrid strokeDasharray="3 3" stroke={gridColor} horizontal={false} />
            <XAxis type="number" tick={{ fontSize: 11, fill: axisColor }} tickFormatter={formatCurrency} />
            <YAxis
              type="category"
              dataKey="name"
              tick={{ fontSize: 11, fill: axisColor }}
              width={110}
            />
            <Tooltip
              formatter={(value) => [formatCurrency(value as number), 'Revenue']}
              contentStyle={{
                borderRadius: 16,
                border: `1px solid ${tooltipBorder}`,
                backgroundColor: tooltipBg,
                boxShadow: isDark
                  ? '0 10px 30px rgba(0,0,0,0.35)'
                  : '0 10px 30px rgba(15,23,42,0.08)',
              }}
              wrapperStyle={{ zIndex: 50 }}
            />
            <Bar dataKey="revenue" fill={barColor} radius={[0, 8, 8, 0]} barSize={20} />
          </BarChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
}