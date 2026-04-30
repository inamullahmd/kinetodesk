import { Outlet } from 'react-router-dom'
import { useEffect, useState } from 'react'
import AppSidebar from './AppSidebar'
import AppHeader from './AppHeader'

export type DashboardHeaderRange = {
  startDate: string
  endDate: string
  label?: string
}

export type DashboardHeaderDateRangeValue = {
  startDate: string
  endDate: string
}

export type DashboardHeaderDateRangeControl = {
  enabled: boolean
  startDate: string
  endDate: string
  minDate?: string
  maxDate?: string
  onChange: (value: DashboardHeaderDateRangeValue) => void
}

export type AppTheme = 'light' | 'dark'

export type DashboardOutletContext = {
  setHeaderRange: (value: DashboardHeaderRange | null) => void
  setHeaderDateRangeControl: (value: DashboardHeaderDateRangeControl | null) => void
  theme: AppTheme
  toggleTheme: () => void
}

export default function DashboardLayout() {
  const [headerRange, setHeaderRange] = useState<DashboardHeaderRange | null>(null)
  const [headerDateRangeControl, setHeaderDateRangeControl] =
    useState<DashboardHeaderDateRangeControl | null>(null)

  const [theme, setTheme] = useState<AppTheme>(() => {
    const stored = window.localStorage.getItem('kinetodesk-theme')
    return stored === 'dark' ? 'dark' : 'light'
  })

  useEffect(() => {
    window.localStorage.setItem('kinetodesk-theme', theme)
  }, [theme])

  const toggleTheme = () => {
    setTheme((prev) => (prev === 'light' ? 'dark' : 'light'))
  }

  return (
    <div
      className={
        theme === 'dark'
          ? 'min-h-screen bg-slate-950 text-slate-100'
          : 'min-h-screen bg-slate-50 text-slate-900'
      }
    >
      <div className="flex min-h-screen">
        <AppSidebar theme={theme} />

        <div className="flex min-w-0 flex-1 flex-col">
          <AppHeader
            headerRange={headerRange}
            headerDateRangeControl={headerDateRangeControl}
            theme={theme}
            onToggleTheme={toggleTheme}
          />

          <main className="flex-1 px-4 py-6 lg:px-8 lg:py-8">
            <div className="mx-auto w-full max-w-[1440px]">
              <Outlet
                context={{
                  setHeaderRange,
                  setHeaderDateRangeControl,
                  theme,
                  toggleTheme,
                }}
              />
            </div>
          </main>
        </div>
      </div>
    </div>
  )
}