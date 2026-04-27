import { useState } from 'react'
import {
  Boxes,
  CalendarDays,
  ChevronDown,
  ClipboardList,
  LayoutDashboard,
  Mail,
  MessageCircleMore,
  Package2,
  Puzzle,
  ShoppingCart,
  Table2,
  Ticket,
  UserCircle2,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { NavLink } from 'react-router-dom'

type LinkItemProps = {
  icon: LucideIcon
  label: string
  to?: string
  badge?: string
  active?: boolean
}

type ExpandableItemProps = {
  icon: LucideIcon
  label: string
  badge?: string
  defaultOpen?: boolean
  children: React.ReactNode
}

function SectionHeading({ children }: { children: React.ReactNode }) {
  return (
    <div className="px-5 pb-3 pt-6 text-[13px] font-semibold uppercase tracking-[0.08em] text-[#B0A8C7]">
      {children}
    </div>
  )
}

function SidebarLink({
  icon: Icon,
  label,
  to,
  badge,
  active = false,
}: LinkItemProps) {
  const content = (
    <>
      <Icon
        size={20}
        strokeWidth={1.9}
        className={active ? 'text-[#6C3BFF]' : 'text-slate-500'}
      />

      <span className="flex-1 text-[15px] font-medium">{label}</span>

      {badge ? (
        <span className="rounded-full bg-[#EAF7EA] px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.06em] text-[#4CAF50]">
          {badge}
        </span>
      ) : null}
    </>
  )

  const className = [
    'flex w-full items-center gap-3 rounded-xl px-3.5 py-3 text-left transition-all',
    active
      ? 'bg-[#F2F0FF] text-[#6C3BFF]'
      : 'text-slate-700 hover:bg-slate-50',
  ].join(' ')

  if (to) {
    return (
      <NavLink to={to} end className={() => className}>
        {content}
      </NavLink>
    )
  }

  return (
    <button type="button" className={className}>
      {content}
    </button>
  )
}

function SidebarExpandable({
  icon: Icon,
  label,
  badge,
  defaultOpen = false,
  children,
}: ExpandableItemProps) {
  const [open, setOpen] = useState(defaultOpen)

  return (
    <div>
      <button
        type="button"
        onClick={() => setOpen((prev) => !prev)}
        className="flex w-full items-center gap-3 rounded-xl px-3.5 py-3 text-left text-slate-700 transition-all hover:bg-slate-50"
      >
        <Icon size={20} strokeWidth={1.9} className="text-slate-500" />

        <span className="flex-1 text-[15px] font-medium">{label}</span>

        {badge ? (
          <span className="rounded-full bg-[#EAF7EA] px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.06em] text-[#4CAF50]">
            {badge}
          </span>
        ) : null}

        <ChevronDown
          size={18}
          strokeWidth={2}
          className={[
            'text-slate-500 transition-transform',
            open ? 'rotate-180' : '',
          ].join(' ')}
        />
      </button>

      {open ? <div className="mt-1 space-y-1 pl-11">{children}</div> : null}
    </div>
  )
}

function SidebarSubLink({
  label,
  active = false,
}: {
  label: string
  active?: boolean
}) {
  return (
    <button
      type="button"
      className={[
        'block w-full rounded-lg px-3 py-2.5 text-left text-[14px] font-medium transition-all',
        active
          ? 'bg-[#F2F0FF] text-[#6C3BFF]'
          : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700',
      ].join(' ')}
    >
      {label}
    </button>
  )
}

export default function AppSidebar() {
  return (
    <aside className="hidden w-[290px] shrink-0 border-r border-slate-200 bg-white lg:flex lg:flex-col">
      <div className="px-5 pb-4 pt-6">
        <div
          className="text-[26px] leading-none font-bold text-slate-950"
          style={{ fontFamily: '"Special Elite", cursive' }}
        >
          kinetodesk.
        </div>

        <div className="mt-2 text-[13px] font-medium text-slate-500">
          Admin Dashboard
        </div>
      </div>

      <div className="flex-1 overflow-y-auto px-3 pb-6">
        <SectionHeading>Menu</SectionHeading>

        <div className="space-y-1.5">
          <SidebarLink
            icon={LayoutDashboard}
            label="Dashboard"
            to="/dashboard"
            active
          />

          <SidebarExpandable
            icon={Boxes}
            label="Inventory"
            defaultOpen
          >
            <SidebarSubLink label="Products" />
            <SidebarSubLink label="Categories" />
            <SidebarSubLink label="Stock Movements" />
          </SidebarExpandable>

          <SidebarLink icon={ShoppingCart} label="Orders" />
          <SidebarLink icon={CalendarDays} label="Calendar" />
          <SidebarLink icon={UserCircle2} label="User Profile" />
          <SidebarLink icon={ClipboardList} label="Tasks" />
          <SidebarLink icon={Table2} label="Tables" />
          <SidebarLink icon={Package2} label="Pages" />
        </div>

        <SectionHeading>Support</SectionHeading>

        <div className="space-y-1.5">
          <SidebarLink icon={MessageCircleMore} label="Chat" />
          <SidebarLink icon={Ticket} label="Support Ticket" badge="New" />
          <SidebarLink icon={Mail} label="Email" />
        </div>

        <SectionHeading>Others</SectionHeading>

        <div className="space-y-1.5">
          <SidebarLink icon={Puzzle} label="UI Elements" />
        </div>
      </div>
    </aside>
  )
}