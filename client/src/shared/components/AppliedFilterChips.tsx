type AppliedFilterChipsProps = {
  chips: string[]
  variant: 'light' | 'dark'
}

export default function AppliedFilterChips({
  chips,
  variant,
}: AppliedFilterChipsProps) {
  const isDark = variant === 'dark'

  if (!chips.length) return null

  return (
    <div className="flex flex-wrap gap-2">
      {chips.map((chip) => (
        <span
          key={chip}
          className={[
            'inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold',
            isDark
              ? 'border-slate-800 bg-slate-950 text-slate-300'
              : 'border-slate-200 bg-slate-50 text-slate-600',
          ].join(' ')}
        >
          {chip}
        </span>
      ))}
    </div>
  )
}