import type { ReactNode } from 'react'
import { useEffect, useState } from 'react'
import {
  Boxes,
  BriefcaseBusiness,
  ChevronDown,
  ExternalLink,
  LayoutDashboard,
  ShoppingCart,
  UsersRound,
  WalletCards,
} from 'lucide-react'
import { NavLink, useLocation } from 'react-router-dom'
import type { AppTheme } from './DashboardLayout'

const PORTFOLIO_URL =
  import.meta.env.VITE_PORTFOLIO_URL || 'https://inamullahmd.com'

const PORTFOLIO_NAME =
  import.meta.env.VITE_PORTFOLIO_NAME || 'Inamullah Mohammad'

type AppSidebarProps = {
  theme: AppTheme
}

type SidebarLinkProps = {
  label: string
  to: string
  icon: ReactNode
  theme: AppTheme
  end?: boolean
}

type SidebarSubLinkProps = {
  label: string
  to: string
  theme: AppTheme
  end?: boolean
}

function getActiveClass(isDark: boolean) {
  return isDark
    ? 'border-blue-500/20 bg-blue-500/10 text-blue-300'
    : 'border-blue-200 bg-blue-50 text-blue-700'
}

function getInactiveClass(isDark: boolean) {
  return isDark
    ? 'border-transparent text-slate-400 hover:bg-slate-900 hover:text-white'
    : 'border-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-950'
}

function SidebarLink({
  label,
  to,
  icon,
  theme,
  end = false,
}: SidebarLinkProps) {
  const isDark = theme === 'dark'

  return (
    <NavLink
      to={to}
      end={end}
      className={({ isActive }) =>
        [
          'flex items-center gap-3 rounded-2xl border px-4 py-3 text-sm font-semibold transition',
          isActive ? getActiveClass(isDark) : getInactiveClass(isDark),
        ].join(' ')
      }
    >
      {icon}
      <span>{label}</span>
    </NavLink>
  )
}

function SidebarSubLink({
  label,
  to,
  theme,
  end = false,
}: SidebarSubLinkProps) {
  const isDark = theme === 'dark'

  return (
    <NavLink
      to={to}
      end={end}
      className={({ isActive }) =>
        [
          'block rounded-xl border px-3 py-2 text-sm font-medium transition',
          isActive ? getActiveClass(isDark) : getInactiveClass(isDark),
        ].join(' ')
      }
    >
      {label}
    </NavLink>
  )
}

function ParentChevron({
  open,
  active,
  isDark,
}: {
  open: boolean
  active: boolean
  isDark: boolean
}) {
  return (
    <ChevronDown
      className={[
        'h-4 w-4 transition',
        open ? 'rotate-180' : '',
        active
          ? isDark
            ? 'text-blue-300'
            : 'text-blue-700'
          : isDark
            ? 'text-slate-500'
            : 'text-slate-400',
      ].join(' ')}
    />
  )
}

export default function AppSidebar({ theme }: AppSidebarProps) {
  const location = useLocation()
  const isDark = theme === 'dark'

  const inventoryActive = location.pathname.startsWith('/inventory')
  const directoryActive = location.pathname.startsWith('/directory')

  const [inventoryOpen, setInventoryOpen] = useState(inventoryActive)
  const [directoryOpen, setDirectoryOpen] = useState(directoryActive)

  useEffect(() => {
    if (inventoryActive) setInventoryOpen(true)
  }, [inventoryActive])

  useEffect(() => {
    if (directoryActive) setDirectoryOpen(true)
  }, [directoryActive])

  const inventoryParentClass = [
    'flex w-full items-center justify-between rounded-2xl border px-4 py-3 text-sm font-semibold transition',
    inventoryActive ? getActiveClass(isDark) : getInactiveClass(isDark),
  ].join(' ')

  const directoryParentClass = [
    'flex w-full items-center justify-between rounded-2xl border px-4 py-3 text-sm font-semibold transition',
    directoryActive ? getActiveClass(isDark) : getInactiveClass(isDark),
  ].join(' ')

  return (
    <aside
      className={[
        'sticky top-0 hidden h-screen w-72 shrink-0 flex-col border-r px-4 py-5 lg:flex',
        isDark
          ? 'border-slate-800 bg-slate-950'
          : 'border-slate-200 bg-white',
      ].join(' ')}
    >
      <div className="mb-7 px-2">
        <div className="flex items-center gap-3">
          <div
            className={[
              'flex h-11 w-11 items-center justify-center rounded-2xl border',
              isDark
                ? 'border-slate-800 bg-slate-900'
                : 'border-slate-200 bg-white',
            ].join(' ')}
          >
            <img
              src="/favicon.svg"
              alt=""
              aria-hidden="true"
              className="h-6 w-6"
            />
          </div>

          <div>
            <div
              className={[
                'font-brand text-[1.45rem] font-black leading-none tracking-[-0.065em]',
                isDark ? 'text-white' : 'text-slate-950',
              ].join(' ')}
            >
              Kinetodesk.
            </div>
          </div>
        </div>
      </div>

      <div
        className={[
          'mb-3 px-2 text-xs font-semibold uppercase tracking-[0.18em]',
          isDark ? 'text-slate-600' : 'text-slate-400',
        ].join(' ')}
      >
        Menu
      </div>

      <nav className="flex flex-1 flex-col gap-2 overflow-y-auto pb-4">
        <SidebarLink
          label="Dashboard"
          to="/dashboard"
          icon={<LayoutDashboard size={18} />}
          theme={theme}
        />

        <SidebarLink
          label="Orders"
          to="/orders"
          icon={<ShoppingCart size={18} />}
          theme={theme}
        />

        <div>
          <button
            type="button"
            onClick={() => setInventoryOpen((prev) => !prev)}
            className={inventoryParentClass}
          >
            <span className="flex items-center gap-3">
              <Boxes size={18} />
              Inventory
            </span>
            <ParentChevron
              open={inventoryOpen}
              active={inventoryActive}
              isDark={isDark}
            />
          </button>

          {inventoryOpen ? (
            <div className="mt-2 space-y-1 pl-8">
              <SidebarSubLink label="Overview" to="/inventory" theme={theme} end />
              <SidebarSubLink label="Products" to="/inventory/products" theme={theme} />
              <SidebarSubLink label="Alerts" to="/inventory/alerts" theme={theme} />
              <SidebarSubLink
                label="Stock Movements"
                to="/inventory/movements"
                theme={theme}
              />
            </div>
          ) : null}
        </div>

        <div>
          <button
            type="button"
            onClick={() => setDirectoryOpen((prev) => !prev)}
            className={directoryParentClass}
          >
            <span className="flex items-center gap-3">
              <UsersRound size={18} />
              Directory
            </span>
            <ParentChevron
              open={directoryOpen}
              active={directoryActive}
              isDark={isDark}
            />
          </button>

          {directoryOpen ? (
            <div className="mt-2 space-y-1 pl-8">
              <SidebarSubLink
                label="Customers"
                to="/directory/customers"
                theme={theme}
              />
              <SidebarSubLink
                label="Employees"
                to="/directory/employees"
                theme={theme}
              />
              <SidebarSubLink
                label="Suppliers"
                to="/directory/suppliers"
                theme={theme}
              />
            </div>
          ) : null}
        </div>

        <SidebarLink
          label="Finance"
          to="/finance/overview"
          icon={<WalletCards size={18} />}
          theme={theme}
        />
      </nav>

      <div
        className={[
          'mt-auto rounded-3xl border p-4',
          isDark
            ? 'border-slate-800 bg-slate-900/60'
            : 'border-slate-200 bg-slate-50',
        ].join(' ')}
      >
        <div>
          <div
            className={[
              'text-[11px] font-semibold uppercase tracking-[0.14em]',
              isDark ? 'text-slate-500' : 'text-slate-400',
            ].join(' ')}
          >
            Developed by
          </div>

          <div
            className={[
              'mt-1 text-sm font-semibold uppercase tracking-[0.16em]',
              isDark ? 'text-white' : 'text-slate-950',
            ].join(' ')}
          >
            {PORTFOLIO_NAME}
          </div>
        </div>

        <a
          href={PORTFOLIO_URL}
          target="_blank"
          rel="noreferrer"
          className={[
            'mt-4 flex items-center justify-center gap-2 rounded-2xl border px-3 py-2.5 text-sm font-semibold transition',
            isDark
              ? 'border-slate-700 bg-slate-950 text-slate-200 hover:bg-slate-900'
              : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
          ].join(' ')}
        >
          <BriefcaseBusiness size={16} />
          Portfolio
          <ExternalLink size={14} />
        </a>
      </div>
    </aside>
  )
}