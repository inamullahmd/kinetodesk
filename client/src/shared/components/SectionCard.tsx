import type { ReactNode } from 'react'

type SectionCardProps = {
  title: string
  description?: string
  action?: ReactNode
  children: ReactNode
  variant?: 'light' | 'dark'
}

export default function SectionCard({
  title,
  description,
  action,
  children,
  variant = 'light',
}: SectionCardProps) {
  const isDark = variant === 'dark'

  return (
    <section
      className={[
        'rounded-[24px] border p-6 shadow-sm',
        isDark
          ? 'border-slate-800 bg-slate-900'
          : 'border-slate-200 bg-white',
      ].join(' ')}
    >
      <div className="mb-5 flex items-start justify-between gap-4">
        <div className="min-w-0">
          <h2
            className={[
              'text-[15px] font-semibold',
              isDark ? 'text-white' : 'text-slate-950',
            ].join(' ')}
          >
            {title}
          </h2>

          {description ? (
            <p
              className={[
                'mt-1.5 text-sm leading-6',
                isDark ? 'text-slate-400' : 'text-slate-500',
              ].join(' ')}
            >
              {description}
            </p>
          ) : null}
        </div>

        {action ? <div className="shrink-0">{action}</div> : null}
      </div>

      <div>{children}</div>
    </section>
  )
}