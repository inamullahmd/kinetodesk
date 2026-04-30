import type { ReactNode } from 'react'
import { TrendingDown , TrendingUp } from 'lucide-react'

type StatCardProps = {
  title: string
  value: string
  secondaryValue?: string
  helperText?: string
  change?: string
  positive?: boolean
  icon?: ReactNode
  compact?: boolean
  variant?: 'light' | 'dark'
  monoSecondary?: boolean
}

export default function StatCard({
  title,
  value,
  secondaryValue,
  helperText,
  change,
  positive = true,
  icon,
  compact = false,
  variant = 'light',
  monoSecondary = false,
}: StatCardProps) {
  const isDark = variant === 'dark'

  const baseCardClass = [
    'rounded-[24px] border shadow-sm',
    isDark
      ? 'border-slate-800 bg-slate-900'
      : 'border-slate-200 bg-white',
  ].join(' ')

  const titleClass = [
    'text-sm font-medium',
    isDark ? 'text-slate-400' : 'text-slate-500',
  ].join(' ')

  const valueClass = compact
    ? [
        'text-[24px] font-semibold leading-[1.15] tracking-tight',
        isDark ? 'text-white' : 'text-slate-950',
      ].join(' ')
    : [
        'text-[30px] font-semibold leading-none tracking-tight',
        isDark ? 'text-white' : 'text-slate-950',
      ].join(' ')

  const secondaryClass = [
    compact ? 'text-sm' : 'text-[15px]',
    monoSecondary ? 'font-data' : '',
    isDark ? 'text-slate-300' : 'text-slate-600',
  ].join(' ')

  const helperClass = [
    'text-sm',
    isDark ? 'text-slate-400' : 'text-slate-500',
  ].join(' ')

  const iconWrapClass = [
    'flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl',
    isDark
      ? 'bg-slate-800 text-violet-300'
      : 'bg-violet-50 text-violet-600',
  ].join(' ')

  if (compact) {
    return (
      <section className={[baseCardClass, 'p-5'].join(' ')}>
        <div className="flex items-start justify-between gap-4">
          <div className={titleClass}>{title}</div>
          {icon ? <div className={iconWrapClass}>{icon}</div> : null}
        </div>

        <div className="mt-2.5 space-y-1.5">
          <div className={valueClass}>{value}</div>

          {secondaryValue ? (
            <div className={secondaryClass}>{secondaryValue}</div>
          ) : null}

          {helperText ? <div className={helperClass}>{helperText}</div> : null}
        </div>
      </section>
    )
  }

  return (
    <section className={[baseCardClass, 'p-6'].join(' ')}>
      <div className="flex items-start justify-between gap-4">
        <div className={titleClass}>{title}</div>
        {icon ? <div className={iconWrapClass}>{icon}</div> : null}
      </div>

      <div className="mt-5 space-y-2">
        <div className={valueClass}>{value}</div>

        {secondaryValue ? (
          <div className={secondaryClass}>{secondaryValue}</div>
        ) : null}

        {helperText ? <div className={helperClass}>{helperText}</div> : null}
      </div>

      {change ? (
        <div
          className={[
            'mt-5 inline-flex items-center gap-1.5 text-sm font-semibold',
            positive
              ? isDark
                ? 'text-emerald-300'
                : 'text-emerald-600'
              : isDark
                ? 'text-rose-300'
                : 'text-rose-600',
          ].join(' ')}
        >
          {positive ? <TrendingUp size={15} /> : <TrendingDown size={15} />}
          <span>{change}</span>
        </div>
      ) : null}
    </section>
  )
}