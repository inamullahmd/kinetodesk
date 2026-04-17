import { useState, type ReactNode } from 'react';
import { useTheme } from '@/hooks/useTheme';
import type { DateRangeOption } from '@/types/overview';
import Sidebar from './Sidebar';
import Topbar from './Topbar';

type DashboardShellProps = {
  children: ReactNode;
  range: DateRangeOption;
  onRangeChange: (value: DateRangeOption) => void;
  customStartDate: string;
  customEndDate: string;
  onCustomStartDateChange: (value: string) => void;
  onCustomEndDateChange: (value: string) => void;
  onApplyCustomRange: () => void;
};

export default function DashboardShell({
  children,
  range,
  onRangeChange,
  customStartDate,
  customEndDate,
  onCustomStartDateChange,
  onCustomEndDateChange,
  onApplyCustomRange,
}: DashboardShellProps) {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const { theme, toggleTheme } = useTheme();

  return (
    <div className="min-h-screen bg-stone-100 text-slate-900 dark:bg-[#0b0d12] dark:text-slate-100">
      <div className="flex min-h-screen">
        <Sidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} />

        <div className="flex min-w-0 flex-1 flex-col">
          <Topbar
            onMenuClick={() => setSidebarOpen(true)}
            theme={theme}
            onToggleTheme={toggleTheme}
            range={range}
            onRangeChange={onRangeChange}
            customStartDate={customStartDate}
            customEndDate={customEndDate}
            onCustomStartDateChange={onCustomStartDateChange}
            onCustomEndDateChange={onCustomEndDateChange}
            onApplyCustomRange={onApplyCustomRange}
          />

          <main className="flex-1 px-3 py-4 sm:px-5 lg:px-6">
            <div className="mx-auto w-full max-w-[1320px]">{children}</div>
          </main>
        </div>
      </div>
    </div>
  );
}