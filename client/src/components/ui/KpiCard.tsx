type KpiCardProps = {
  title: string;
  value: string | number;
  hint?: string;
};

export default function KpiCard({ title, value, hint }: KpiCardProps) {
  return (
    <div className="rounded-[22px] border border-black/6 bg-white px-4 py-3.5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] dark:border-white/6 dark:bg-[#151a24]">
      <p className="text-[10px] font-medium uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
        {title}
      </p>
      <div className="mt-2 text-[1.55rem] leading-none font-semibold tracking-[-0.04em] text-slate-950 dark:text-white">
        {value}
      </div>
      {hint ? (
        <p className="mt-2 text-[11px] leading-5 text-slate-400 dark:text-slate-500">
          {hint}
        </p>
      ) : null}
    </div>
  );
}