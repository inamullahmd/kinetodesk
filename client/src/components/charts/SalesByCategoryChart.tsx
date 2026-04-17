import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';

type SalesByCategoryChartProps = {
  data: Array<{
    name: string;
    value: number;
  }>;
};

type CategoryTooltipPayload = {
  payload: {
    name: string;
    value: number;
  };
};

type CustomTooltipProps = {
  active?: boolean;
  payload?: CategoryTooltipPayload[];
};

const LIGHT_COLORS = ['#6366F1', '#8B5CF6', '#0EA5E9', '#14B8A6', '#F59E0B', '#EC4899'];
const DARK_COLORS = ['#8B5CF6', '#A78BFA', '#38BDF8', '#5EEAD4', '#FBBF24', '#F472B6'];

function formatCurrency(value: number | string) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    notation: 'compact',
    maximumFractionDigits: 1,
  }).format(Number(value));
}

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
        Revenue:{' '}
        <span className="font-semibold text-slate-900 dark:text-white">
          {formatCurrency(point.value)}
        </span>
      </p>
    </div>
  );
}

export default function SalesByCategoryChart({ data }: SalesByCategoryChartProps) {
  const isDark = document.documentElement.classList.contains('dark');
  const colors = isDark ? DARK_COLORS : LIGHT_COLORS;

  return (
    <div className="rounded-[28px] border border-black/6 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] dark:border-white/6 dark:bg-[#151a24]">
      <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
        Category Mix
      </p>
      <h2 className="mt-1 text-[1.35rem] font-bold tracking-[-0.03em] text-slate-950 dark:text-white">
        Sales by category
      </h2>

      <div className="mt-5 flex flex-col items-center">
        <div className="h-[220px] w-full max-w-[260px] sm:h-[240px] sm:max-w-[280px]">
          <ResponsiveContainer width="100%" height="100%">
            <PieChart>
              <Tooltip content={<CustomTooltip />} wrapperStyle={{ zIndex: 50 }} />
              <Pie
                data={data}
                dataKey="value"
                nameKey="name"
                innerRadius={62}
                outerRadius={96}
                paddingAngle={3}
              >
                {data.map((entry, index) => (
                  <Cell key={entry.name} fill={colors[index % colors.length]} />
                ))}
              </Pie>
            </PieChart>
          </ResponsiveContainer>
        </div>

        <div className="mt-5 grid w-full grid-cols-1 gap-3 sm:grid-cols-2">
          {data.map((item, index) => (
            <div key={item.name} className="flex items-center gap-3 rounded-2xl border border-slate-200 px-3 py-2.5 dark:border-white/8 dark:bg-white/[0.02]">
              <span
                className="h-3 w-3 shrink-0 rounded-full"
                style={{ backgroundColor: colors[index % colors.length] }}
              />
              <div className="min-w-0">
                <div className="truncate text-[13px] font-medium text-slate-800 dark:text-slate-100">
                  {item.name}
                </div>
                <div className="text-[12px] text-slate-500 dark:text-slate-400">
                  {formatCurrency(item.value)}
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}