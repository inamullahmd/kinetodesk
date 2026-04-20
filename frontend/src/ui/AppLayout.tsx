import type { ReactNode } from "react";
import {
  LayoutDashboard,
  Package,
  LogOut,
  MonitorSmartphone,
  ChevronRight,
  ShoppingBasket,
  ReceiptText,
} from "lucide-react";
import { Link, Outlet, useLocation, useNavigate } from "react-router-dom";
import { logout } from "../api/auth";
import { useAuthStore } from "../store/auth";

function SidebarItem({
  to,
  label,
  icon,
}: {
  to: string;
  label: string;
  icon: ReactNode;
}) {
  const location = useLocation();
  const active = location.pathname === to;

  return (
    <Link
      to={to}
      className={[
        "group flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition-all",
        active
          ? "bg-slate-900 text-white shadow-sm"
          : "text-slate-600 hover:bg-slate-100 hover:text-slate-900",
      ].join(" ")}
    >
      <span
        className={[
          "flex h-9 w-9 items-center justify-center rounded-xl transition-all",
          active
            ? "bg-white/10 text-white"
            : "bg-slate-100 text-slate-600 group-hover:bg-white",
        ].join(" ")}
      >
        {icon}
      </span>
      <span>{label}</span>
    </Link>
  );
}

function pageMeta(pathname: string) {
  if (pathname === "/products") {
    return {
      title: "Products",
      subtitle: "Browse catalog, stock levels, and pricing.",
      breadcrumb: "Catalog",
    };
  }

  if (pathname === "/purchase-orders") {
    return {
      title: "Purchase Orders",
      subtitle: "Track supplier orders, expected receipts, and replenishment.",
      breadcrumb: "Purchasing",
    };
  }

  if (pathname === "/orders") {
    return {
      title: "Orders",
      subtitle: "Review sales orders, channels, and fulfillment status.",
      breadcrumb: "Sales",
    };
  }

  return {
    title: "Dashboard",
    subtitle: "Overview of store operations, revenue, and stock activity.",
    breadcrumb: "Overview",
  };
}

export function AppLayout() {
  const navigate = useNavigate();
  const location = useLocation();
  const { user, clearAuth } = useAuthStore();

  const meta = pageMeta(location.pathname);

  async function handleLogout() {
    try {
      await logout();
    } catch {
      //
    } finally {
      clearAuth();
      navigate("/login");
    }
  }

  return (
    <div className="min-h-screen bg-slate-100">
      <div className="mx-auto grid min-h-screen max-w-[1680px] grid-cols-1 lg:grid-cols-[280px_1fr]">
        <aside className="border-r border-slate-200 bg-white px-5 py-6">
          <div className="flex items-center gap-3 px-2">
            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-sm">
              <MonitorSmartphone className="h-5 w-5" />
            </div>

            <div>
              <h1 className="text-lg font-semibold tracking-tight text-slate-900">
                KinetoDesk
              </h1>
              <p className="text-xs font-medium uppercase tracking-[0.18em] text-slate-400">
                Admin Console
              </p>
            </div>
          </div>

          <div className="mt-8 rounded-3xl border border-slate-200 bg-slate-50 p-4">
            <p className="text-xs font-medium uppercase tracking-[0.18em] text-slate-400">
              Signed in
            </p>
            <p className="mt-2 text-sm font-semibold text-slate-900">
              {user?.name ?? "Admin"}
            </p>
            <p className="mt-0.5 text-xs text-slate-500">{user?.email ?? ""}</p>
          </div>

          <nav className="mt-8 space-y-2">
            <SidebarItem
              to="/"
              label="Dashboard"
              icon={<LayoutDashboard className="h-4 w-4" />}
            />
            <SidebarItem
              to="/products"
              label="Products"
              icon={<Package className="h-4 w-4" />}
            />
            <SidebarItem
              to="/purchase-orders"
              label="Purchase Orders"
              icon={<ShoppingBasket className="h-4 w-4" />}
            />
            <SidebarItem
              to="/orders"
              label="Orders"
              icon={<ReceiptText className="h-4 w-4" />}
            />
          </nav>

          <div className="mt-10 rounded-3xl bg-slate-900 p-5 text-white">
            <p className="text-xs font-medium uppercase tracking-[0.18em] text-slate-400">
              Workspace
            </p>
            <h3 className="mt-2 text-base font-semibold">Store Management</h3>
            <p className="mt-2 text-sm leading-6 text-slate-300">
              Inventory, sales, custom builds, service, and reporting in one place.
            </p>
          </div>
        </aside>

        <main className="min-h-screen">
          <header className="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div className="px-6 py-5 lg:px-8">
              <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                  <div className="flex items-center gap-2 text-xs font-medium uppercase tracking-[0.18em] text-slate-400">
                    <span>KinetoDesk</span>
                    <ChevronRight className="h-3.5 w-3.5" />
                    <span>{meta.breadcrumb}</span>
                  </div>
                  <h2 className="mt-2 text-2xl font-semibold tracking-tight text-slate-900">
                    {meta.title}
                  </h2>
                  <p className="mt-1 text-sm text-slate-500">{meta.subtitle}</p>
                </div>

                <button
                  onClick={handleLogout}
                  className="inline-flex items-center gap-2 self-start rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                  <LogOut className="h-4 w-4" />
                  Logout
                </button>
              </div>
            </div>
          </header>

          <div className="px-6 py-6 lg:px-8">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}