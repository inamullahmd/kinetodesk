import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';
import type { CustomerTypeSalesPoint } from '@/types/overview';

type SalesByCustomerTypeChartProps = {
  data: CustomerTypeSalesPoint[];
};

const LIGHT_TYPE_COLORS: Record<string, string> = {
  retail: '#6366F1',
  business: '#10B981',
};

const DARK_TYPE_COLORS: Record<string, string> = {
  retail: '#8B5CF6',
  business: '#34D399',
};

function formatCurrency(value: number | string) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    notation: 'compact',
    maximumFractionDigits: 1,
  }).format(Number(value));
}

function toTitleCase(value: string) {
  return value.charAt(0).toUpperCase() + value.slice(1);
}

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
        Customer Type
      </p>
      <p className="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
        {toTitleCase(point.name)}
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

export default function SalesByCustomerTypeChart({ data }: SalesByCustomerTypeChartProps) {
  const isDark = document.documentElement.classList.contains('dark');
  const typeColors = isDark ? DARK_TYPE_COLORS : LIGHT_TYPE_COLORS;
  const total = data.reduce((sum, item) => sum + Number(item.value), 0);

  return (
    <div className="rounded-[28px] border border-black/6 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] dark:border-white/6 dark:bg-[#151a24]">
      <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
        Customer Mix
      </p>
      <h2 className="mt-1 text-[1.35rem] font-bold tracking-[-0.03em] text-slate-950 dark:text-white">
        Sales by customer type
      </h2>

      <div className="mt-5 flex flex-col items-center">
        <div className="relative h-[220px] w-full max-w-[260px] sm:h-[240px] sm:max-w-[280px]">
          <ResponsiveContainer width="100%" height="100%">
            <PieChart>
              <Tooltip
                content={<CustomTooltip />}
                wrapperStyle={{ zIndex: 50 }}
                contentStyle={{ backgroundColor: '#ffffff', opacity: 1 }}
              />
              <Pie
                data={data}
                dataKey="value"
                nameKey="name"
                innerRadius={68}
                outerRadius={98}
                paddingAngle={4}
              >
                {data.map((entry) => (
                  <Cell
                    key={entry.name}
                    fill={typeColors[entry.name.toLowerCase()] ?? (isDark ? '#a78bfa' : '#8B5CF6')}
                  />
                ))}
              </Pie>
            </PieChart>
          </ResponsiveContainer>

          <div className="pointer-events-none absolute inset-0 z-0 flex items-center justify-center">
            <div className="text-center">
              <div className="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                Total
              </div>
              <div className="mt-1 text-[1.05rem] font-bold text-slate-950 dark:text-white">
                {formatCurrency(total)}
              </div>
            </div>
          </div>
        </div>

        <div className="mt-5 grid w-full grid-cols-1 gap-3 sm:grid-cols-2">
          {data.map((item) => {
            const value = Number(item.value);
            const share = total > 0 ? (value / total) * 100 : 0;

            return (
              <div key={item.name} className="flex items-center gap-3 rounded-2xl border border-slate-200 px-3 py-2.5 dark:border-white/8 dark:bg-white/[0.02]">
                <span
                  className="h-3 w-3 shrink-0 rounded-full"
                  style={{
                    backgroundColor:
                      typeColors[item.name.toLowerCase()] ?? (isDark ? '#a78bfa' : '#8B5CF6'),
                  }}
                />
                <div className="min-w-0">
                  <div className="truncate text-[13px] font-medium text-slate-800 dark:text-slate-100">
                    {toTitleCase(item.name)}
                  </div>
                  <div className="text-[12px] text-slate-500 dark:text-slate-400">
                    {formatCurrency(value)} · {share.toFixed(1)}%
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}