import {
  Area,
  AreaChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';
import type { RevenueTrendPoint } from '@/types/overview';

type RevenueTrendChartProps = {
  data: RevenueTrendPoint[];
};

function formatCurrency(value: number | string) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 2,
  }).format(Number(value));
}

function formatDate(value: string) {
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(value));
}

export default function RevenueTrendChart({ data }: RevenueTrendChartProps) {
  const isDark = document.documentElement.classList.contains('dark');

  const gridColor = isDark ? '#2a3140' : '#d7dce5';
  const axisColor = isDark ? '#94a3b8' : '#64748b';
  const tooltipBg = isDark ? '#11141d' : '#ffffff';
  const tooltipBorder = isDark ? '#273042' : '#dbe1ea';
  const tooltipText = isDark ? '#e5e7eb' : '#0f172a';
  const strokeColor = isDark ? '#8b5cf6' : '#6d5df6';
  const fillTop = isDark ? 0.34 : 0.26;
  const fillBottom = isDark ? 0.05 : 0.02;

  return (
    <div className="flex h-full flex-col rounded-[28px] border border-black/6 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] dark:border-white/6 dark:bg-[#151a24]">
      <div className="mb-4">
        <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
          Revenue Trend
        </p>
        <h2 className="mt-1 text-[1.55rem] font-bold tracking-[-0.03em] text-slate-950 dark:text-white">
          Completed sales over time
        </h2>
      </div>

      <div className="min-h-[300px] w-full flex-1">
        <ResponsiveContainer width="100%" height="100%">
          <AreaChart data={data}>
            <defs>
              <linearGradient id="revenueFill" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor={strokeColor} stopOpacity={fillTop} />
                <stop offset="95%" stopColor={strokeColor} stopOpacity={fillBottom} />
              </linearGradient>
            </defs>

            <CartesianGrid strokeDasharray="3 3" stroke={gridColor} />
            <XAxis dataKey="date" tick={{ fontSize: 11, fill: axisColor }} minTickGap={28} />
            <YAxis tick={{ fontSize: 11, fill: axisColor }} />
            <Tooltip
              labelFormatter={(label) => formatDate(String(label))}
              formatter={(value) => [formatCurrency(value as number | string), 'Revenue']}
              contentStyle={{
                borderRadius: 16,
                border: `1px solid ${tooltipBorder}`,
                backgroundColor: tooltipBg,
                color: tooltipText,
                boxShadow: isDark
                  ? '0 10px 30px rgba(0,0,0,0.35)'
                  : '0 10px 30px rgba(15,23,42,0.08)',
              }}
              wrapperStyle={{ zIndex: 50 }}
            />
            <Area
              type="monotone"
              dataKey="revenue"
              stroke={strokeColor}
              fill="url(#revenueFill)"
              strokeWidth={2.5}
            />
          </AreaChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
}