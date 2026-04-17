import {
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';

type LowStockByCategoryChartProps = {
  data: Array<{
    name: string;
    value: number;
  }>;
};

const COLORS = ['#F43F5E', '#FB7185', '#F97316', '#F59E0B', '#8B5CF6', '#6366F1'];

type CustomTooltipProps = {
  active?: boolean;
  payload?: Array<{
    payload: {
      name: string;
      value: number;
    };
  }>;
};

function CustomTooltip({ active, payload }: CustomTooltipProps) {
  if (!active || !payload || !payload.length) {
    return null;
  }

  const point = payload[0]?.payload;

  if (!point) {
    return null;
  }

  return (
    <div className="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-xl dark:border-white/10 dark:bg-[#11141d]">
      <p className="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
        Category
      </p>
      <p className="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
        {point.name}
      </p>
      <p className="mt-2 text-[13px] text-slate-600 dark:text-slate-300">
        At-risk SKUs:{' '}
        <span className="font-semibold text-slate-900 dark:text-white">
          {point.value.toLocaleString()}
        </span>
      </p>
    </div>
  );
}

export default function LowStockByCategoryChart({ data }: LowStockByCategoryChartProps) {
  return (
    <div className="rounded-[28px] border border-black/6 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] dark:border-white/6 dark:bg-[#151a24]">
      <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
        Inventory Risk
      </p>
      <h2 className="mt-1 text-[1.45rem] font-bold tracking-[-0.03em] text-slate-950 dark:text-white">
        Low stock by category
      </h2>

      <div className="mt-5 h-[320px]">
        <ResponsiveContainer width="100%" height="100%">
          <BarChart data={data} margin={{ top: 8, right: 10, left: 0, bottom: 6 }}>
            <CartesianGrid strokeDasharray="3 3" stroke="#d7dce5" vertical={false} />
            <XAxis dataKey="name" tick={{ fontSize: 11, fill: '#64748b' }} />
            <YAxis tick={{ fontSize: 11, fill: '#64748b' }} allowDecimals={false} />
            <Tooltip content={<CustomTooltip />} />
            <Bar dataKey="value" radius={[10, 10, 0, 0]} barSize={38}>
              {data.map((entry, index) => (
                <Cell key={entry.name} fill={COLORS[index % COLORS.length]} />
              ))}
            </Bar>
          </BarChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
}