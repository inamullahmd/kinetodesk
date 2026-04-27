import { TrendingDown, TrendingUp } from 'lucide-react'

type StatCardProps = {
  title: string
  value: string
  secondaryValue?: string
  change?: string
  positive?: boolean
  compact?: boolean
}

export default function StatCard({
  title,
  value,
  secondaryValue,
  change,
  positive,
  compact = false,
}: StatCardProps) {
  return (
    <div
      className={`flex h-full flex-col rounded-2xl border border-slate-200 bg-white shadow-sm ${
        compact
          ? 'justify-between px-5 py-5 text-left'
          : 'min-h-[180px] items-center justify-center px-3 py-5 text-center'
      }`}
    >
      <p className="text-sm font-medium tracking-[0.02em] text-slate-500">
        {title}
      </p>

      <p
        className={`mt-3 font-bold leading-tight text-slate-950 ${
          compact
            ? 'line-clamp-2 text-[1.15rem] tracking-[0.01em]'
            : 'text-[2.2rem] tracking-[0.03em] xl:text-[2.5rem]'
        }`}
      >
        {value}
      </p>

      {secondaryValue ? (
        <p className="mt-2 text-sm tracking-[0.015em] text-slate-500">
          {secondaryValue}
        </p>
      ) : null}

      {change ? (
        <div
          className={`mt-4 inline-flex items-center gap-1.5 text-sm font-medium tracking-[0.01em] ${
            positive ? 'text-emerald-600' : 'text-rose-600'
          } ${compact ? '' : 'justify-center'}`}
        >
          {positive ? <TrendingUp size={16} /> : <TrendingDown size={16} />}
          <span>{change}</span>
        </div>
      ) : null}
    </div>
  )
}