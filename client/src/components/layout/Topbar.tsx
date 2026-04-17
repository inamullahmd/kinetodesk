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
    <header className="border-b border-black/5 bg-stone-100/80 px-3 py-3 backdrop-blur-sm dark:border-white/5 dark:bg-[#0b0d12]/80 sm:px-5">
      <div className="mx-auto flex w-full max-w-[1320px] flex-wrap items-start justify-between gap-3">
        <div className="flex items-start gap-3">
          <button
            type="button"
            onClick={onMenuClick}
            className="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm lg:hidden dark:border-white/10 dark:bg-[#11141d] dark:text-slate-200"
          >
            ☰
          </button>

          <div>
            <h1 className="text-[1.75rem] font-bold tracking-[-0.04em] text-slate-900 dark:text-white">
              Overview
            </h1>
            <p className="mt-1 text-[13px] text-slate-500 dark:text-slate-400">
              Executive snapshot of sales, purchasing, and inventory performance
            </p>
          </div>
        </div>

        <div className="flex flex-wrap items-center justify-end gap-2.5">
          <div className="hidden rounded-2xl border border-slate-200 bg-white px-3.5 py-2 text-[13px] font-medium text-slate-700 shadow-sm sm:block dark:border-white/10 dark:bg-[#11141d] dark:text-slate-200">
            Norman Store
          </div>

          <RangeSelect value={range} onChange={onRangeChange} />

          {range === 'custom' ? (
            <div className="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm dark:border-white/10 dark:bg-[#11141d]">
              <input
                type="date"
                value={customStartDate}
                onChange={(e) => onCustomStartDateChange(e.target.value)}
                className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-[13px] font-medium text-slate-700 outline-none focus:border-indigo-500 dark:border-white/10 dark:bg-[#0d1018] dark:text-slate-200"
              />

              <span className="text-sm text-slate-400">—</span>

              <input
                type="date"
                value={customEndDate}
                onChange={(e) => onCustomEndDateChange(e.target.value)}
                className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-[13px] font-medium text-slate-700 outline-none focus:border-indigo-500 dark:border-white/10 dark:bg-[#0d1018] dark:text-slate-200"
              />

              <button
                type="button"
                onClick={onApplyCustomRange}
                className="rounded-xl bg-slate-900 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200"
              >
                Apply
              </button>
            </div>
          ) : null}

          <button
            type="button"
            onClick={onToggleTheme}
            aria-label={theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'}
            title={theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'}
            className="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-white/10 dark:bg-[#11141d] dark:text-slate-200 dark:hover:bg-[#171b25]"
          >
            {theme === 'dark' ? (
              <svg viewBox="0 0 24 24" className="h-4.5 w-4.5" fill="none" aria-hidden="true">
                <path
                  d="M12 3V5.5M12 18.5V21M5.64 5.64L7.41 7.41M16.59 16.59L18.36 18.36M3 12H5.5M18.5 12H21M5.64 18.36L7.41 16.59M16.59 7.41L18.36 5.64M16 12A4 4 0 1 1 8 12A4 4 0 0 1 16 12Z"
                  stroke="currentColor"
                  strokeWidth="1.8"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                />
              </svg>
            ) : (
              <svg viewBox="0 0 24 24" className="h-4.5 w-4.5" fill="none" aria-hidden="true">
                <path
                  d="M21 12.8A9 9 0 1 1 11.2 3C10.95 3.8 10.82 4.65 10.82 5.53C10.82 10.21 14.61 14 19.29 14C19.88 14 20.45 13.94 21 13.8V12.8Z"
                  stroke="currentColor"
                  strokeWidth="1.8"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                />
              </svg>
            )}
          </button>
        </div>
      </div>
    </header>
  );
}