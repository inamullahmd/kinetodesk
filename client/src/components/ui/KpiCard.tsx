type KpiCardProps = {
  title: string;
  value: string | number;
  hint?: string;
};

export default function KpiCard({ title, value, hint }: KpiCardProps) {
  return (
    <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition dark:border-slate-800 dark:bg-slate-900">
      <p className="text-sm font-medium text-slate-500 dark:text-slate-400">{title}</p>
      <div className="mt-3 text-3xl font-semibold tracking-[-0.03em] text-slate-900 dark:text-white">
        {value}
      </div>
      {hint ? <p className="mt-2 text-sm text-slate-400">{hint}</p> : null}
    </div>
  );
}