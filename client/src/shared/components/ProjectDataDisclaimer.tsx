import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { CheckCircle2, Info, X } from 'lucide-react'
import type { AppTheme } from '../../app/DashboardLayout'

const STORAGE_KEY = 'kinetodesk-demo-disclaimer-accepted'

export default function ProjectDataDisclaimer({
  theme,
}: {
  theme: AppTheme
}) {
  const [open, setOpen] = useState(false)
  const [dontShowAgain, setDontShowAgain] = useState(true)
  const isDark = theme === 'dark'

  useEffect(() => {
    const accepted = window.localStorage.getItem(STORAGE_KEY)

    if (!accepted) {
      setOpen(true)
    }
  }, [])

  function closeDisclaimer() {
    if (dontShowAgain) {
      window.localStorage.setItem(STORAGE_KEY, 'true')
    }

    setOpen(false)
  }

  if (!open) return null

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/70 px-4 py-6 backdrop-blur-md">
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="project-disclaimer-title"
        className={[
          'w-full max-w-2xl rounded-[2rem] border p-6 shadow-2xl',
          isDark
            ? 'border-slate-800 bg-slate-950 text-slate-100 shadow-black/40'
            : 'border-slate-200 bg-white text-slate-950 shadow-slate-950/20',
        ].join(' ')}
      >
        <div className="flex items-start justify-between gap-5">
          <div className="flex min-w-0 items-start gap-4">
            <div
              className={[
                'flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl',
                isDark
                  ? 'bg-blue-500/10 text-blue-300 ring-1 ring-blue-500/20'
                  : 'bg-blue-50 text-blue-700 ring-1 ring-blue-100',
              ].join(' ')}
            >
              <Info className="h-5 w-5" />
            </div>

            <div className="min-w-0">
              <h2
                id="project-disclaimer-title"
                className="text-2xl font-semibold tracking-tight"
              >
                Disclaimer
              </h2>

              <p
                className={`mt-2 text-sm leading-6 ${
                  isDark ? 'text-slate-300' : 'text-slate-600'
                }`}
              >
                Kinetodesk. is a demonstration project. All data shown in this
                application is fictional and generated for portfolio purposes
                only.
              </p>
            </div>
          </div>

          <button
            type="button"
            onClick={closeDisclaimer}
            className={[
              'flex h-10 w-10 shrink-0 items-center justify-center rounded-full border transition',
              isDark
                ? 'border-slate-800 text-slate-400 hover:bg-slate-900 hover:text-white'
                : 'border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-950',
            ].join(' ')}
            aria-label="Close disclaimer"
          >
            <X className="h-4 w-4" />
          </button>
        </div>

        <div
          className={[
            'mt-6 grid gap-3 rounded-3xl border p-4 sm:grid-cols-2',
            isDark
              ? 'border-slate-800 bg-slate-900/50'
              : 'border-slate-200 bg-slate-50',
          ].join(' ')}
        >
          <DisclosureItem
            title="Fictional Data"
            description="Names, customers, suppliers, employees, and locations."
            variant={theme}
          />

          <DisclosureItem
            title="Synthetic Records"
            description="Orders, payments, refunds, inventory, and financial values."
            variant={theme}
          />
        </div>

        <p
          className={`mt-5 text-sm leading-6 ${
            isDark ? 'text-slate-400' : 'text-slate-500'
          }`}
        >
          This app is not connected to a real business and should not be
          interpreted as real financial, accounting, inventory, customer, or
          operational data.
        </p>

        <div className="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <label
            className={`inline-flex select-none items-center gap-2 text-sm ${
              isDark ? 'text-slate-400' : 'text-slate-600'
            }`}
          >
            <input
              type="checkbox"
              checked={dontShowAgain}
              onChange={(event) => setDontShowAgain(event.target.checked)}
              className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
            />
            Do not show this again
          </label>

          <div className="flex flex-col-reverse gap-3 sm:flex-row sm:items-center">

            <button
              type="button"
              onClick={closeDisclaimer}
              className={[
                'inline-flex items-center justify-center rounded-2xl px-5 py-3 text-sm font-semibold transition',
                isDark
                  ? 'bg-blue-600 text-white hover:bg-blue-500'
                  : 'bg-slate-950 text-white hover:bg-slate-800',
              ].join(' ')}
            >
              Continue
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}

function DisclosureItem({
  title,
  description,
  variant,
}: {
  title: string
  description: string
  variant: AppTheme
}) {
  const isDark = variant === 'dark'

  return (
    <div className="flex gap-3">
      <CheckCircle2
        className={[
          'mt-0.5 h-4 w-4 shrink-0',
          isDark ? 'text-blue-300' : 'text-blue-600',
        ].join(' ')}
      />

      <div className="min-w-0">
        <div
          className={`text-sm font-semibold ${
            isDark ? 'text-white' : 'text-slate-950'
          }`}
        >
          {title}
        </div>

        <div
          className={`mt-1 text-xs leading-5 ${
            isDark ? 'text-slate-400' : 'text-slate-500'
          }`}
        >
          {description}
        </div>
      </div>
    </div>
  )
}