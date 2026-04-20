import { useQuery } from "@tanstack/react-query";
import {
  AlertTriangle,
  DollarSign,
  FolderKanban,
  ShoppingCart,
  Wrench,
} from "lucide-react";
import { getDashboardOverview } from "../api/dashboard";

function formatMoney(value: number | string | null | undefined) {
  const numericValue = Number(value ?? 0);
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    maximumFractionDigits: 2,
  }).format(numericValue);
}

function MetricCard({
  label,
  value,
  helper,
  icon,
}: {
  label: string;
  value: string;
  helper: string;
  icon: React.ReactNode;
}) {
  return (
    <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
      <div className="flex items-start justify-between gap-4">
        <div>
          <p className="text-sm font-medium text-slate-500">{label}</p>
          <p className="mt-3 text-3xl font-semibold tracking-tight text-slate-900">
            {value}
          </p>
          <p className="mt-2 text-xs text-slate-400">{helper}</p>
        </div>

        <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
          {icon}
        </div>
      </div>
    </div>
  );
}

function SectionCard({
  title,
  subtitle,
  children,
}: {
  title: string;
  subtitle: string;
  children: React.ReactNode;
}) {
  return (
    <section className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
      <div className="mb-4">
        <h3 className="text-lg font-semibold tracking-tight text-slate-900">
          {title}
        </h3>
        <p className="mt-1 text-sm text-slate-500">{subtitle}</p>
      </div>
      {children}
    </section>
  );
}

export function DashboardPage() {
  const { data, isLoading, error } = useQuery({
    queryKey: ["dashboard-overview"],
    queryFn: getDashboardOverview,
  });

  if (isLoading) {
    return (
      <div className="rounded-3xl border border-slate-200 bg-white p-8 text-sm text-slate-500 shadow-sm">
        Loading dashboard...
      </div>
    );
  }

  if (error) {
    return (
      <div className="rounded-3xl border border-red-200 bg-red-50 p-6 text-sm text-red-700 shadow-sm">
        Failed to load dashboard.
      </div>
    );
  }

  const today = data?.summary?.today ?? {};
  const month = data?.summary?.month ?? {};
  const year = data?.summary?.year ?? {};

  const inventory = data?.inventory ?? {};
  const service = data?.service ?? {};

  const recentOrders = data?.recent_orders ?? [];
  const recentServiceTickets = service?.recent_service_tickets ?? [];

  return (
    <div className="space-y-6">
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <MetricCard
          label="Today Revenue"
          value={formatMoney(today.revenue)}
          helper={`${today.orders_count ?? 0} orders today`}
          icon={<DollarSign className="h-5 w-5" />}
        />
        <MetricCard
          label="Month Revenue"
          value={formatMoney(month.revenue)}
          helper={`${month.orders_count ?? 0} orders this month`}
          icon={<ShoppingCart className="h-5 w-5" />}
        />
        <MetricCard
          label="Year Revenue"
          value={formatMoney(year.revenue)}
          helper={`${year.orders_count ?? 0} orders this year`}
          icon={<FolderKanban className="h-5 w-5" />}
        />
        <MetricCard
          label="Low Stock Count"
          value={String(inventory.low_stock_count ?? 0)}
          helper="Products near reorder threshold"
          icon={<AlertTriangle className="h-5 w-5" />}
        />
        <MetricCard
          label="Reorder Alerts"
          value={String(inventory.reorder_alert_count ?? 0)}
          helper="Tracked replenishment alerts"
          icon={<AlertTriangle className="h-5 w-5" />}
        />
        <MetricCard
          label="Open Service Tickets"
          value={String(service.open_tickets_count ?? 0)}
          helper="Active repair workload"
          icon={<Wrench className="h-5 w-5" />}
        />
      </div>

      <div className="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <SectionCard
          title="Recent Orders"
          subtitle="Latest sales activity across the store."
        >
          <div className="space-y-3">
            {recentOrders.length === 0 ? (
              <div className="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                No recent orders found.
              </div>
            ) : (
              recentOrders.map((order: any) => (
                <div
                  key={order.id}
                  className="rounded-2xl border border-slate-200 px-4 py-4 transition hover:bg-slate-50"
                >
                  <div className="flex items-center justify-between gap-4">
                    <div>
                      <p className="font-semibold text-slate-900">
                        {order.order_number}
                      </p>
                      <p className="mt-1 text-sm text-slate-500">
                        Status: {order.status}
                      </p>
                    </div>

                    <div className="text-right">
                      <p className="text-sm font-semibold text-slate-900">
                        {formatMoney(order.total_amount)}
                      </p>
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>
        </SectionCard>

        <SectionCard
          title="Recent Service Tickets"
          subtitle="Current ticket activity and repair flow."
        >
          <div className="space-y-3">
            {recentServiceTickets.length === 0 ? (
              <div className="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                No recent service tickets found.
              </div>
            ) : (
              recentServiceTickets.map((ticket: any) => (
                <div
                  key={ticket.id}
                  className="rounded-2xl border border-slate-200 px-4 py-4 transition hover:bg-slate-50"
                >
                  <p className="font-semibold text-slate-900">
                    {ticket.ticket_number}
                  </p>
                  <p className="mt-1 text-sm text-slate-500">
                    Status: {ticket.status}
                  </p>
                </div>
              ))
            )}
          </div>
        </SectionCard>
      </div>
    </div>
  );
}