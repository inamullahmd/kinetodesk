import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';

type OrderStatusChartProps = {
  data: Array<{
    name: string;
    value: number;
  }>;
};

type StatusTooltipPayload = {
  payload: {
    name: string;
    value: number;
  };
};

type CustomTooltipProps = {
  active?: boolean;
  payload?: StatusTooltipPayload[];
};

const STATUS_COLORS: Record<string, string> = {
  completed: '#22c55e',
  pending: '#f59e0b',
  refunded: '#94a3b8',
  cancelled: '#f43f5e',
};

function toTitleCase(value: string) {
  return value.charAt(0).toUpperCase() + value.slice(1);
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
      <p className="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
        {toTitleCase(point.name)}
      </p>
      <p className="mt-2 text-[13px] text-slate-600 dark:text-slate-300">
        Orders:{' '}
        <span className="font-semibold text-slate-900 dark:text-white">
          {point.value.toLocaleString()}
        </span>
      </p>
    </div>
  );
}

export default function OrderStatusChart({ data }: OrderStatusChartProps) {
  return (
    <div className="rounded-[28px] border border-black/6 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] dark:border-white/6 dark:bg-[#151a24]">
      <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
        Order Health
      </p>
      <h2 className="mt-1 text-[1.45rem] font-bold tracking-[-0.03em] text-slate-950 dark:text-white">
        Orders by status
      </h2>

      <div className="mt-4 flex items-center gap-4">
        <div className="h-[250px] flex-1">
          <ResponsiveContainer width="100%" height="100%">
            <PieChart>
              <Tooltip content={<CustomTooltip />} />
              <Pie
                data={data}
                dataKey="value"
                nameKey="name"
                innerRadius={62}
                outerRadius={95}
                paddingAngle={3}
              >
                {data.map((entry) => (
                  <Cell
                    key={entry.name}
                    fill={STATUS_COLORS[entry.name.toLowerCase()] ?? '#6d5df6'}
                  />
                ))}
              </Pie>
            </PieChart>
          </ResponsiveContainer>
        </div>

        <div className="min-w-[145px] space-y-3">
          {data.map((item) => (
            <div key={item.name} className="flex items-center gap-3">
              <span
                className="h-3 w-3 rounded-full"
                style={{
                  backgroundColor: STATUS_COLORS[item.name.toLowerCase()] ?? '#6d5df6',
                }}
              />
              <div>
                <div className="text-[13px] font-medium capitalize text-slate-800 dark:text-slate-100">
                  {item.name}
                </div>
                <div className="text-[12px] text-slate-500 dark:text-slate-400">
                  {item.value.toLocaleString()}
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}