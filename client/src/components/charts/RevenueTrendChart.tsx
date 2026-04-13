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
  return (
    <div className="flex h-full flex-col rounded-[28px] border border-black/6 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] dark:border-white/6 dark:bg-[#151a24]">
      <div className="mb-4">
        <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
          Revenue Trend
        </p>
        <h2 className="mt-1 text-[1.75rem] font-bold tracking-[-0.03em] text-slate-950 dark:text-white">
          Completed sales over time
        </h2>
      </div>

      <div className="min-h-[330px] flex-1 w-full">
        <ResponsiveContainer width="100%" height="100%">
          <AreaChart data={data}>
            <defs>
              <linearGradient id="revenueFill" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="#6d5df6" stopOpacity={0.26} />
                <stop offset="95%" stopColor="#6d5df6" stopOpacity={0.02} />
              </linearGradient>
            </defs>

            <CartesianGrid strokeDasharray="3 3" stroke="#d7dce5" />
            <XAxis dataKey="date" tick={{ fontSize: 11, fill: '#64748b' }} minTickGap={28} />
            <YAxis tick={{ fontSize: 11, fill: '#64748b' }} />
            <Tooltip
              labelFormatter={(label) => formatDate(String(label))}
              formatter={(value) => [formatCurrency(value as number | string), 'Revenue']}
              contentStyle={{
                borderRadius: 16,
                border: '1px solid #dbe1ea',
                boxShadow: '0 10px 30px rgba(15,23,42,0.08)',
              }}
            />
            <Area
              type="monotone"
              dataKey="revenue"
              stroke="#6d5df6"
              fill="url(#revenueFill)"
              strokeWidth={2.5}
            />
          </AreaChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
}