type ChartToggleOption<T extends string> = {
  label: string
  value: T
}

type ChartToggleProps<T extends string> = {
  value: T
  onChange: (value: T) => void
  options: [ChartToggleOption<T>, ChartToggleOption<T>]
}

export default function ChartToggle<T extends string>({
  value,
  onChange,
  options,
}: ChartToggleProps<T>) {
  return (
    <div className="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 p-1">
      {options.map((option) => {
        const active = value === option.value

        return (
          <button
            key={option.value}
            type="button"
            onClick={() => onChange(option.value)}
            className={`rounded-lg px-4 py-2 text-sm font-medium transition-all ${
              active
                ? 'bg-white text-slate-900 shadow-[0_1px_2px_rgba(16,24,40,0.06)]'
                : 'text-slate-500 hover:text-slate-700'
            }`}
          >
            {option.label}
          </button>
        )
      })}
    </div>
  )
}