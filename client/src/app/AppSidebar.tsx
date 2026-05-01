import type { ReactNode } from 'react'
import { useEffect, useState } from 'react'
import {
  Boxes,
  CalendarDays,
  ChevronDown,
  ClipboardList,
  LayoutDashboard,
  ShoppingCart,
  Table2,
  UserCircle,
  UsersRound,
  WalletCards,
} from 'lucide-react'
import { NavLink, useLocation } from 'react-router-dom'
import type { AppTheme } from './DashboardLayout'

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
      <span className="flex h-5 w-5 items-center justify-center">
        {icon}
      </span>
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
      size={16}
      className={[
        'transition-transform',
        open ? 'rotate-180' : '',
        active
          ? isDark
            ? 'text-blue-300'
            : 'text-blue-600'
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
    if (inventoryActive) {
      setInventoryOpen(true)
    }
  }, [inventoryActive])

  useEffect(() => {
    if (directoryActive) {
      setDirectoryOpen(true)
    }
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
        'hidden min-h-screen w-[290px] shrink-0 border-r px-5 py-7 lg:block',
        isDark
          ? 'border-slate-800 bg-slate-950'
          : 'border-slate-200 bg-white',
      ].join(' ')}
    >
      <div className="mb-10">
        <div
          className={[
            'text-[28px] font-extrabold tracking-tight',
            isDark ? 'text-white' : 'text-slate-950',
          ].join(' ')}
        >
          kinetodesk.
        </div>

        <div
          className={[
            'mt-1 text-sm font-medium',
            isDark ? 'text-slate-400' : 'text-slate-500',
          ].join(' ')}
        >
          Admin Dashboard
        </div>
      </div>

      <div
        className={[
          'mb-4 px-3 text-xs font-bold uppercase tracking-[0.18em]',
          isDark ? 'text-slate-600' : 'text-slate-400',
        ].join(' ')}
      >
        Menu
      </div>

      <nav className="space-y-1">
        <SidebarLink
          label="Dashboard"
          to="/dashboard"
          end
          icon={<LayoutDashboard size={19} />}
          theme={theme}
        />

        <div>
          <button
            type="button"
            onClick={() => setInventoryOpen((prev) => !prev)}
            className={inventoryParentClass}
          >
            <span className="flex items-center gap-3">
              <Boxes size={19} />
              <span>Inventory</span>
            </span>

            <ParentChevron open={inventoryOpen} active={inventoryActive} isDark={isDark} />
          </button>

          {inventoryOpen ? (
            <div className="ml-8 mt-2 space-y-1">
              <SidebarSubLink
                label="Overview"
                to="/inventory"
                end
                theme={theme}
              />

              <SidebarSubLink
                label="Products"
                to="/inventory/products"
                theme={theme}
              />

              <SidebarSubLink
                label="Alerts"
                to="/inventory/alerts"
                theme={theme}
              />

              <SidebarSubLink
                label="Stock Movements"
                to="/inventory/movements"
                theme={theme}
              />
            </div>
          ) : null}
        </div>

        <SidebarLink
          label="Orders"
          to="/orders"
          icon={<ShoppingCart size={19} />}
          theme={theme}
        />

        <div>
          <button
            type="button"
            onClick={() => setDirectoryOpen((prev) => !prev)}
            className={directoryParentClass}
          >
            <span className="flex items-center gap-3">
              <UsersRound size={19} />
              <span>Directory</span>
            </span>

            <ParentChevron open={directoryOpen} active={directoryActive} isDark={isDark} />
          </button>

          {directoryOpen ? (
            <div className="ml-8 mt-2 space-y-1">
              <SidebarSubLink
                label="Customers"
                to="/directory/customers"
                theme={theme}
              />

              <SidebarSubLink
                label="Suppliers"
                to="/directory/suppliers"
                theme={theme}
              />

              <SidebarSubLink
                label="Employees"
                to="/directory/employees"
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

        <SidebarLink
          label="Calendar"
          to="/calendar"
          icon={<CalendarDays size={19} />}
          theme={theme}
        />

        <SidebarLink
          label="User Profile"
          to="/profile"
          icon={<UserCircle size={19} />}
          theme={theme}
        />

        <SidebarLink
          label="Tasks"
          to="/tasks"
          icon={<ClipboardList size={19} />}
          theme={theme}
        />

        <SidebarLink
          label="Tables"
          to="/tables"
          icon={<Table2 size={19} />}
          theme={theme}
        />

        <SidebarLink
          label="Pages"
          to="/pages"
          icon={<WalletCards size={19} />}
          theme={theme}
        />
      </nav>
    </aside>
  )
}