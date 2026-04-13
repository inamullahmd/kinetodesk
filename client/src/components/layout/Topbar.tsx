import RangeSelect from '@/components/ui/RangeSelect';
import type { DateRangeOption } from '@/types/overview';

type TopbarProps = {
  onMenuClick: () => void;
  theme: 'light' | 'dark';
  onToggleTheme: () => void;
  range: DateRangeOption;
  onRangeChange: (value: DateRangeOption) => void;
  customStartDate: string;
  customEndDate: string;
  onCustomStartDateChange: (value: string) => void;
  onCustomEndDateChange: (value: string) => void;
  onApplyCustomRange: () => void;
};

export default function Topbar({
  onMenuClick,
  theme,
  onToggleTheme,
  range,
  onRangeChange,
  customStartDate,
  customEndDate,
  onCustomStartDateChange,
  onCustomEndDateChange,
  onApplyCustomRange,
}: TopbarProps) {
  return (
    <header className="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 bg-white/80 px-4 py-4 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-950/70 sm:px-6">
      <div className="flex items-start gap-3">
        <button
          type="button"
          onClick={onMenuClick}
          className="mt-1 inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm lg:hidden dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"
        >
          ☰
        </button>

        <div>
          <h1 className="text-3xl font-bold tracking-[-0.03em] text-slate-900 dark:text-white">
            Overview
          </h1>
          <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Executive snapshot of sales, purchasing, and inventory performance
          </p>
        </div>
      </div>

      <div className="flex flex-wrap items-center justify-end gap-3">
        <div className="hidden rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm sm:block dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
          Norman Store
        </div>

        <RangeSelect value={range} onChange={onRangeChange} />

        {range === 'custom' ? (
          <div className="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <input
              type="date"
              value={customStartDate}
              onChange={(e) => onCustomStartDateChange(e.target.value)}
              className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
            />

            <span className="text-sm text-slate-400">—</span>

            <input
              type="date"
              value={customEndDate}
              onChange={(e) => onCustomEndDateChange(e.target.value)}
              className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
            />

            <button
              type="button"
              onClick={onApplyCustomRange}
              className="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200"
            >
              Apply
            </button>
          </div>
        ) : null}

        <button
          type="button"
          onClick={onToggleTheme}
          className="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
        >
          {theme === 'dark' ? 'Light Mode' : 'Dark Mode'}
        </button>
      </div>
    </header>
  );
}