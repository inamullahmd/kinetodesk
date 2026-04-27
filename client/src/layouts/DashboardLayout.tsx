import { Outlet } from 'react-router-dom'
import { useState } from 'react'
import AppSidebar from '../components/layout/AppSidebar'
import AppHeader from '../components/layout/AppHeader'

export type DashboardOutletContext = {
  setAsOfDate: (value: string | null) => void
}

export default function DashboardLayout() {
  const [asOfDate, setAsOfDate] = useState<string | null>(null)

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900">
      <div className="flex min-h-screen">
        <AppSidebar />

        <div className="flex min-w-0 flex-1 flex-col">
          <AppHeader asOfDate={asOfDate} />

          <main className="flex-1 px-4 py-6 lg:px-8 lg:py-8">
            <div className="mx-auto w-full max-w-[1440px]">
              <Outlet context={{ setAsOfDate }} />
            </div>
          </main>
        </div>
      </div>
    </div>
  )
}