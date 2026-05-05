import { useEffect, useMemo, useRef, useState } from 'react'
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-react'

type DatePickerInputProps = {
  value: string
  onChange: (value: string) => void
  minDate?: string
  maxDate?: string
  placeholder?: string
  variant?: 'light' | 'dark'
  size?: 'sm' | 'md'
  showIcon?: boolean
  appearance?: 'default' | 'ghost'
  className?: string
}

const WEEKDAYS = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']

function pad(value: number) {
  return String(value).padStart(2, '0')
}

function toIsoDate(date: Date) {
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

function parseIsoDate(value?: string) {
  if (!value) return null

  const [year, month, day] = value.split('-').map(Number)

  if (!year || !month || !day) return null

  return new Date(year, month - 1, day)
}

function formatDisplayDate(value?: string) {
  const date = parseIsoDate(value)

  if (!date) return ''

  return date.toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

function isBeforeMin(dateIso: string, minDate?: string) {
  return Boolean(minDate && dateIso < minDate)
}

function isAfterMax(dateIso: string, maxDate?: string) {
  return Boolean(maxDate && dateIso > maxDate)
}

function getMonthDays(monthDate: Date) {
  const year = monthDate.getFullYear()
  const month = monthDate.getMonth()

  const firstDayOfMonth = new Date(year, month, 1)
  const lastDayOfMonth = new Date(year, month + 1, 0)

  const leadingEmptyDays = firstDayOfMonth.getDay()
  const daysInMonth = lastDayOfMonth.getDate()

  const days: Array<{
    date: Date | null
    isoDate: string | null
    label: string
  }> = []

  for (let index = 0; index < leadingEmptyDays; index += 1) {
    days.push({
      date: null,
      isoDate: null,
      label: '',
    })
  }

  for (let day = 1; day <= daysInMonth; day += 1) {
    const date = new Date(year, month, day)

    days.push({
      date,
      isoDate: toIsoDate(date),
      label: String(day),
    })
  }

  return days
}

export default function DatePickerInput({
  value,
  onChange,
  minDate,
  maxDate,
  placeholder = 'Select date',
  variant = 'light',
  size = 'sm',
  showIcon = true,
  appearance = 'default',
  className = '',
}: DatePickerInputProps) {
  const isDark = variant === 'dark'
  const wrapperRef = useRef<HTMLDivElement | null>(null)

  const selectedDate = parseIsoDate(value)

  const [isOpen, setIsOpen] = useState(false)
  const [visibleMonth, setVisibleMonth] = useState<Date>(() => {
    return selectedDate ?? new Date()
  })

  useEffect(() => {
    if (selectedDate) {
      setVisibleMonth(selectedDate)
    }
  }, [value])

  useEffect(() => {
    function handlePointerDown(event: MouseEvent) {
      if (!wrapperRef.current?.contains(event.target as Node)) {
        setIsOpen(false)
      }
    }

    function handleEscape(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        setIsOpen(false)
      }
    }

    document.addEventListener('mousedown', handlePointerDown)
    document.addEventListener('keydown', handleEscape)

    return () => {
      document.removeEventListener('mousedown', handlePointerDown)
      document.removeEventListener('keydown', handleEscape)
    }
  }, [])

  const days = useMemo(() => getMonthDays(visibleMonth), [visibleMonth])

  const monthLabel = visibleMonth.toLocaleDateString(undefined, {
    month: 'long',
    year: 'numeric',
  })

  const buttonClass =
    appearance === 'ghost'
      ? isDark
        ? 'border-transparent bg-transparent text-slate-100 hover:bg-slate-800/60'
        : 'border-transparent bg-transparent text-slate-900 hover:bg-slate-100'
      : isDark
        ? 'border-slate-700 bg-slate-950 text-slate-100 hover:border-slate-500'
        : 'border-slate-200 bg-white text-slate-900 hover:border-slate-300'

  const popoverClass = isDark
    ? 'border-slate-700 bg-slate-950 text-slate-100 shadow-2xl shadow-black/30'
    : 'border-slate-200 bg-white text-slate-900 shadow-2xl shadow-slate-200/70'

  const mutedTextClass = isDark ? 'text-slate-400' : 'text-slate-500'

  const navButtonClass = isDark
    ? 'text-slate-200 hover:bg-slate-800'
    : 'text-slate-700 hover:bg-slate-100'

  const selectedDayClass = isDark
    ? 'bg-slate-100 text-slate-950'
    : 'bg-slate-900 text-white'

  const normalDayClass = isDark
    ? 'text-slate-100 hover:bg-slate-800'
    : 'text-slate-800 hover:bg-slate-100'

  const disabledDayClass = isDark
    ? 'cursor-not-allowed text-slate-700'
    : 'cursor-not-allowed text-slate-300'

  const sizeClasses = {
    sm: {
      trigger: 'h-8 rounded-lg px-2 text-xs',
      popover: 'w-56 rounded-xl p-2',
      navButton: 'h-7 w-7 rounded-md',
      monthLabel: 'text-xs',
      weekday: 'text-[10px]',
      day: 'h-7 rounded-md text-xs',
      gridGap: 'gap-0.5',
      icon: 'h-3.5 w-3.5',
      chevron: 'h-3.5 w-3.5',
    },
    md: {
      trigger: 'h-10 rounded-xl px-3 text-sm',
      popover: 'w-72 rounded-2xl p-3',
      navButton: 'h-8 w-8 rounded-lg',
      monthLabel: 'text-sm',
      weekday: 'text-xs',
      day: 'h-9 rounded-lg text-sm',
      gridGap: 'gap-1',
      icon: 'h-4 w-4',
      chevron: 'h-4 w-4',
    },
  }

  const datePickerSize = sizeClasses[size]

  function goToPreviousMonth() {
    setVisibleMonth((current) => new Date(current.getFullYear(), current.getMonth() - 1, 1))
  }

  function goToNextMonth() {
    setVisibleMonth((current) => new Date(current.getFullYear(), current.getMonth() + 1, 1))
  }

  function selectDate(dateIso: string) {
    if (isBeforeMin(dateIso, minDate) || isAfterMax(dateIso, maxDate)) {
      return
    }

    onChange(dateIso)
    setIsOpen(false)
  }

  return (
    <div ref={wrapperRef} className={`relative ${className}`}>
      <button
        type="button"
        onClick={() => setIsOpen((current) => !current)}
        className={`flex w-full items-center justify-between border text-left transition ${datePickerSize.trigger} ${buttonClass}`}
      >
        <span className={value ? '' : mutedTextClass}>
          {value ? formatDisplayDate(value) : placeholder}
        </span>

        {showIcon ? (
          <CalendarDays className={`shrink-0 ${datePickerSize.icon} ${mutedTextClass}`} />
        ) : null}
      </button>

      {isOpen ? (
        <div
          className={`absolute left-0 z-50 mt-2 border ${datePickerSize.popover} ${popoverClass}`}
          onMouseDown={(event) => event.preventDefault()}
        >
          <div className="mb-2 flex items-center justify-between">
            <button
              type="button"
              onClick={goToPreviousMonth}
              className={`flex items-center justify-center transition ${datePickerSize.navButton} ${navButtonClass}`}
              aria-label="Previous month"
            >
              <ChevronLeft className={datePickerSize.chevron} />
            </button>

            <div className={`${datePickerSize.monthLabel} font-semibold`}>{monthLabel}</div>

            <button
              type="button"
              onClick={goToNextMonth}
              className={`flex items-center justify-center transition ${datePickerSize.navButton} ${navButtonClass}`}
              aria-label="Next month"
            >
              <ChevronRight className={datePickerSize.chevron} />
            </button>
          </div>

          <div
            className={`mb-1.5 grid grid-cols-7 text-center font-semibold ${datePickerSize.weekday} ${mutedTextClass}`}
          >
            {WEEKDAYS.map((weekday) => (
              <div key={weekday} className="py-1">
                {weekday}
              </div>
            ))}
          </div>

          <div className={`grid grid-cols-7 ${datePickerSize.gridGap}`}>
            {days.map((day, index) => {
              if (!day.isoDate) {
                return <div key={`empty-${index}`} className={datePickerSize.day} />
              }

              const isoDate = day.isoDate

              const isSelected = isoDate === value
              const isDisabled = isBeforeMin(isoDate, minDate) || isAfterMax(isoDate, maxDate)

              return (
                <button
                  key={isoDate}
                  type="button"
                  disabled={isDisabled}
                  onClick={() => selectDate(isoDate)}
                  className={`${datePickerSize.day} font-medium transition ${
                    isDisabled
                      ? disabledDayClass
                      : isSelected
                        ? selectedDayClass
                        : normalDayClass
                  }`}
                >
                  {day.label}
                </button>
              )
            })}
          </div>
        </div>
      ) : null}
    </div>
  )
}