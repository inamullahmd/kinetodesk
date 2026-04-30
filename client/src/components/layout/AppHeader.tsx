import { Bell, CalendarDays, Moon, Search, Sun } from 'lucide-react'
import { useLocation } from 'react-router-dom'
import type {
  AppTheme,
  DashboardHeaderDateRangeControl,
  DashboardHeaderRange,
} from '../../layouts/DashboardLayout'

type AppHeaderProps = {
  headerRange: DashboardHeaderRange | null
  headerDateRangeControl: DashboardHeaderDateRangeControl | null
  theme: AppTheme
  onToggleTheme: () => void
}

function getPageMeta(pathname: string) {
  if (pathname.startsWith('/orders')) {
    return {
      title: 'Orders',
      subtitle: 'Sales and purchase order activity',
    }
  }

  if (pathname.startsWith('/inventory')) {
    return {
      title: 'Inventory',
      subtitle: 'Stock, alerts, and product availability',
    }
  }

  if (pathname.startsWith('/customers')) {
    return {
      title: 'Customers',
      subtitle: 'Customer geography and sales activity',
    }
  }

  return {
    title: 'Overview',
    subtitle: 'Business summary and activity',
  }
}

function formatDateRange(startDate: string, endDate: string) {
  const start = new Date(`${startDate}T00:00:00`)
  const end = new Date(`${endDate}T00:00:00`)
  const sameYear = start.getFullYear() === end.getFullYear()

  const startLabel = start.toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    ...(sameYear ? {} : { year: 'numeric' }),
  })

  const endLabel = end.toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  })

  return `${startLabel} - ${endLabel}`
}

function clampIsoDate(value: string, minDate?: string, maxDate?: string) {
  if (!value) return value
  if (minDate && value < minDate) return minDate
  if (maxDate && value > maxDate) return maxDate
  return value
}

function HeaderDateRangeInput({
  control,
  isDark,
}: {
  control: DashboardHeaderDateRangeControl
  isDark: boolean
}) {
  const inputClass = [
    'h-9 rounded-lg border px-2.5 text-sm outline-none transition',
    isDark
      ? 'border-slate-800 bg-slate-900 text-slate-200 focus:border-blue-500'
      : 'border-slate-200 bg-white text-slate-700 focus:border-blue-500',
  ].join(' ')

  const updateStartDate = (value: string) => {
    const nextStart = clampIsoDate(value, control.minDate, control.maxDate)
    const nextEnd = nextStart > control.endDate ? nextStart : control.endDate

    control.onChange({
      startDate: nextStart,
      endDate: clampIsoDate(nextEnd, nextStart, control.maxDate),
    })
  }

  const updateEndDate = (value: string) => {
    const nextEnd = clampIsoDate(value, control.startDate, control.maxDate)

    control.onChange({
      startDate: control.startDate > nextEnd ? nextEnd : control.startDate,
      endDate: nextEnd,
    })
  }

  return (
    <div
      className={[
        'hidden h-12 items-center gap-2 rounded-xl border px-3 py-0 lg:flex',
        isDark
          ? 'border-slate-800 bg-slate-950 text-slate-300'
          : 'border-slate-200 bg-slate-50 text-slate-500',
      ].join(' ')}
    >
      <CalendarDays
        size={16}
        className={isDark ? 'text-slate-500' : 'text-slate-400'}
      />

      <input
        type="date"
        value={control.startDate}
        min={control.minDate}
        max={control.maxDate}
        onChange={(event) => updateStartDate(event.target.value)}
        className={inputClass}
      />

      <span className={isDark ? 'text-slate-600' : 'text-slate-300'}>
        —
      </span>

      <input
        type="date"
        value={control.endDate}
        min={control.startDate}
        max={control.maxDate}
        onChange={(event) => updateEndDate(event.target.value)}
        className={inputClass}
      />
    </div>
  )
}

export default function AppHeader({
  headerRange,
  headerDateRangeControl,
  theme,
  onToggleTheme,
}: AppHeaderProps) {
  const location = useLocation()
  const pageMeta = getPageMeta(location.pathname)
  const isDark = theme === 'dark'

  const avatarUrl = 'https://i.pravatar.cc/96?img=32'

  const formattedRange =
    headerRange && headerRange.startDate && headerRange.endDate
      ? headerRange.label ?? formatDateRange(headerRange.startDate, headerRange.endDate)
      : null

  const iconButtonClass = [
    'flex h-10 w-10 items-center justify-center rounded-full border transition',
    isDark
      ? 'border-slate-800 bg-slate-900 text-slate-300 hover:bg-slate-800'
      : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
  ].join(' ')

  return (
    <header
      className={[
        'border-b px-6 py-4 lg:px-8',
        isDark
          ? 'border-slate-800 bg-slate-950'
          : 'border-slate-200 bg-white',
      ].join(' ')}
    >
      <div className="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
          <h2
            className={`text-lg font-semibold ${
              isDark ? 'text-white' : 'text-slate-900'
            }`}
          >
            {pageMeta.title}
          </h2>

          <p
            className={`text-sm ${
              isDark ? 'text-slate-400' : 'text-slate-500'
            }`}
          >
            {pageMeta.subtitle}
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-3">

          {headerDateRangeControl?.enabled ? (
            <HeaderDateRangeInput
              control={headerDateRangeControl}
              isDark={isDark}
            />
          ) : formattedRange ? (
            <div
              className={[
                'hidden h-12 items-center gap-2 rounded-xl border px-4 py-0 text-sm lg:flex',
                isDark
                  ? 'border-slate-800 bg-slate-900 text-slate-300'
                  : 'border-slate-200 bg-slate-50 text-slate-500',
              ].join(' ')}
            >
              <CalendarDays
                size={16}
                className={isDark ? 'text-slate-500' : 'text-slate-400'}
              />

              <span>{formattedRange}</span>
            </div>
          ) : null}

          <button
            type="button"
            onClick={onToggleTheme}
            aria-label={isDark ? 'Switch to light mode' : 'Switch to dark mode'}
            title={isDark ? 'Switch to light mode' : 'Switch to dark mode'}
            className={iconButtonClass}
          >
            {isDark ? <Sun size={18} /> : <Moon size={18} />}
          </button>

          <button
            type="button"
            aria-label="Notifications"
            title="Notifications"
            className={iconButtonClass}
          >
            <Bell size={18} />
          </button>

          <button
            type="button"
            aria-label="Open user profile"
            title="User profile"
            className={[
              'flex h-10 w-10 items-center justify-center rounded-full border p-1 transition',
              isDark
                ? 'border-slate-800 bg-slate-900 hover:bg-slate-800'
                : 'border-slate-200 bg-white hover:bg-slate-50',
            ].join(' ')}
          >
            <img
              src={avatarUrl}
              alt="User avatar"
              className="h-8 w-8 rounded-full object-cover"
            />
          </button>
        </div>
      </div>
    </header>
  )
}