import { Bell, CalendarDays, Search } from 'lucide-react'

type AppHeaderProps = {
  asOfDate: string | null
}

export default function AppHeader({ asOfDate }: AppHeaderProps) {
  const formattedDate = asOfDate
    ? new Date(`${asOfDate}T00:00:00`).toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
      })
    : null

  return (
    <header className="border-b border-slate-200 bg-white px-6 py-4 lg:px-8">
      <div className="flex items-center justify-between gap-4">
        <div>
          <h2 className="text-lg font-semibold">Overview</h2>
          <p className="text-sm text-slate-500">Business summary and activity</p>
        </div>

        <div className="flex items-center gap-3">
          {formattedDate ? (
            <div className="hidden items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-500 lg:flex">
              <CalendarDays size={16} className="text-slate-400" />
              <span>As of {formattedDate}</span>
            </div>
          ) : null}

          <div className="hidden items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 md:flex">
            <Search size={16} className="text-slate-400" />
            <span className="text-sm text-slate-400">Search...</span>
          </div>

          <button className="rounded-xl border border-slate-200 bg-white p-2 text-slate-600 hover:bg-slate-50">
            <Bell size={18} />
          </button>
        </div>
      </div>
    </header>
  )
}