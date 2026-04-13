type InsightStatCardProps = {
  label: string;
  value: string | number;
  tone?: 'default' | 'accent' | 'success' | 'warning';
  hint?: string;
};

export default function InsightStatCard({
  label,
  value,
  tone = 'default',
  hint,
}: InsightStatCardProps) {
  const toneClasses =
    tone === 'accent'
      ? 'bg-indigo-50 border-indigo-200/70 dark:bg-indigo-500/10 dark:border-indigo-500/20'
      : tone === 'success'
        ? 'bg-emerald-50 border-emerald-200/70 dark:bg-emerald-500/10 dark:border-emerald-500/20'
        : tone === 'warning'
          ? 'bg-amber-50 border-amber-200/70 dark:bg-amber-500/10 dark:border-amber-500/20'
          : 'bg-stone-100 border-black/5 dark:bg-white/[0.04] dark:border-white/6';

  return (
    <div className={`rounded-[22px] border px-4 py-4 ${toneClasses}`}>
      <p className="text-[11px] font-medium uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">
        {label}
      </p>
      <div className="mt-2 text-[1.7rem] leading-none font-semibold tracking-[-0.04em] text-slate-950 dark:text-white">
        {value}
      </div>
      {hint ? (
        <p className="mt-2 text-[12px] leading-5 text-slate-500 dark:text-slate-400">
          {hint}
        </p>
      ) : null}
    </div>
  );
}