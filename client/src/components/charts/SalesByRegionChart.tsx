import type { RegionSalesPoint } from '@/types/overview';

type SalesByRegionChartProps = {
  data: RegionSalesPoint[];
};

const LIGHT_REGION_STYLES: Record<string, { bg: string; chip: string }> = {
  Norman: {
    bg: 'bg-indigo-500',
    chip: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300',
  },
  'Rest of Oklahoma': {
    bg: 'bg-sky-500',
    chip: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
  },
  'Out of State': {
    bg: 'bg-emerald-500',
    chip: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
  },
};

const DARK_REGION_STYLES: Record<string, { bg: string; chip: string }> = {
  Norman: {
    bg: 'bg-violet-500',
    chip: 'bg-violet-500/15 text-violet-300',
  },
  'Rest of Oklahoma': {
    bg: 'bg-cyan-400',
    chip: 'bg-cyan-500/15 text-cyan-300',
  },
  'Out of State': {
    bg: 'bg-emerald-400',
    chip: 'bg-emerald-500/15 text-emerald-300',
  },
};

function formatCurrency(value: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    notation: 'compact',
    maximumFractionDigits: 1,
  }).format(value);
}

function formatPercent(value: number) {
  return `${value.toFixed(1)}%`;
}

export default function SalesByRegionChart({ data }: SalesByRegionChartProps) {
  const isDark = document.documentElement.classList.contains('dark');
  const styles = isDark ? DARK_REGION_STYLES : LIGHT_REGION_STYLES;
  const total = data.reduce((sum, item) => sum + Number(item.value), 0);

  return (
    <div className="rounded-[28px] border border-black/6 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] dark:border-white/6 dark:bg-[#151a24]">
      <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
        Geographic Mix
      </p>
      <h2 className="mt-1 text-[1.35rem] font-bold tracking-[-0.03em] text-slate-950 dark:text-white">
        Sales by region
      </h2>

      <div className="mt-5 overflow-hidden rounded-[22px] border border-slate-200 dark:border-white/8">
        <div className="flex h-5 w-full overflow-hidden bg-slate-100 dark:bg-white/[0.04]">
          {data.map((item) => {
            const width = total > 0 ? (Number(item.value) / total) * 100 : 0;
            const style = styles[item.name] ?? styles.Norman;

            return (
              <div
                key={item.name}
                className={`${style.bg} h-full transition-all`}
                style={{ width: `${width}%` }}
                title={`${item.name}: ${formatCurrency(Number(item.value))}`}
              />
            );
          })}
        </div>
      </div>

      <div className="mt-5 space-y-3">
        {data.map((item) => {
          const value = Number(item.value);
          const share = total > 0 ? (value / total) * 100 : 0;
          const style = styles[item.name] ?? styles.Norman;

          return (
            <div
              key={item.name}
              className="flex items-center justify-between gap-4 rounded-[20px] border border-slate-200 px-4 py-3 dark:border-white/8 dark:bg-white/[0.02]"
            >
              <div className="flex min-w-0 items-center gap-3">
                <span className={`h-3 w-3 rounded-full ${style.bg}`} />
                <div className="min-w-0">
                  <div className="truncate text-[14px] font-semibold text-slate-900 dark:text-white">
                    {item.name}
                  </div>
                  <div className="mt-1 text-[12px] text-slate-500 dark:text-slate-400">
                    {formatCurrency(value)}
                  </div>
                </div>
              </div>

              <span
                className={`shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold ${style.chip}`}
              >
                {formatPercent(share)}
              </span>
            </div>
          );
        })}
      </div>
    </div>
  );
}