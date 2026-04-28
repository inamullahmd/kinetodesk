import type { ReactNode } from 'react'

type SectionCardProps = {
  title: string
  children: ReactNode
  action?: ReactNode
}

export default function SectionCard({ title, children, action }: SectionCardProps) {
  return (
    <section className="h-full min-w-0 max-w-full overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div className="mb-5 flex items-center justify-between gap-4">
        <h3 className="text-base font-bold">{title}</h3>
        {action ? <div className="shrink-0">{action}</div> : null}
      </div>

      <div className="min-w-0 max-w-full">
        {children}
      </div>
    </section>
  )
}