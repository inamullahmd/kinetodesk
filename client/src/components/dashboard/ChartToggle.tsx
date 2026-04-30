type ChartToggleOption<T extends string> = {
  label: string
  value: T
}

type ChartToggleProps<T extends string> = {
  value: T
  onChange: (value: T) => void
  options: ChartToggleOption<T>[]
  variant?: 'light' | 'dark'
}

export default function ChartToggle<T extends string>({
  value,
  onChange,
  options,
  variant = 'light',
}: ChartToggleProps<T>) {
  const isDark = variant === 'dark'

  return (
    <div
      className={[
        'inline-flex rounded-xl border p-1',
        isDark
          ? 'border-slate-800 bg-slate-950'
          : 'border-slate-200 bg-slate-50',
      ].join(' ')}
    >
      {options.map((option) => {
        const active = option.value === value

        return (
          <button
            key={option.value}
            type="button"
            onClick={() => onChange(option.value)}
            className={[
              'rounded-lg px-5 py-2 text-sm font-semibold transition',
              active
                ? isDark
                  ? 'bg-slate-800 text-blue-300 shadow-sm'
                  : 'bg-white text-blue-600 shadow-sm'
                : isDark
                  ? 'text-slate-400 hover:text-slate-200'
                  : 'text-slate-500 hover:text-slate-700',
            ].join(' ')}
          >
            {option.label}
          </button>
        )
      })}
    </div>
  )
}