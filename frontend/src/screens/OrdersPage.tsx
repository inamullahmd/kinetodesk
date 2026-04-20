import { useQuery } from "@tanstack/react-query";
import {
  ReceiptText,
  Search,
  SlidersHorizontal,
  ShoppingCart,
  Store,
} from "lucide-react";
import { useMemo, useState } from "react";
import { getOrders } from "@/api/orders";

function formatMoney(value: number | string | null | undefined) {
  const numericValue = Number(value ?? 0);
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    maximumFractionDigits: 2,
  }).format(numericValue);
}

function statusTone(status: string) {
  switch (status) {
    case "completed":
      return "bg-emerald-50 text-emerald-700";
    case "paid":
      return "bg-blue-50 text-blue-700";
    case "confirmed":
      return "bg-amber-50 text-amber-700";
    case "cancelled":
      return "bg-red-50 text-red-700";
    case "draft":
      return "bg-slate-100 text-slate-700";
    default:
      return "bg-slate-100 text-slate-700";
  }
}

function channelTone(channel: string) {
  switch (channel) {
    case "online":
      return "bg-violet-50 text-violet-700";
    case "in_store":
      return "bg-sky-50 text-sky-700";
    case "walk_in":
      return "bg-emerald-50 text-emerald-700";
    case "phone":
      return "bg-amber-50 text-amber-700";
    default:
      return "bg-slate-100 text-slate-700";
  }
}

export function OrdersPage() {
  const [searchInput, setSearchInput] = useState("");
  const [search, setSearch] = useState("");

  const { data, isLoading, error } = useQuery({
    queryKey: ["orders", search],
    queryFn: () => getOrders({ search }),
  });

  const orders = useMemo(() => data?.data ?? [], [data]);

  const visibleRevenue = useMemo(() => {
    return orders.reduce((sum: number, order: any) => {
      return sum + Number(order.total_amount ?? 0);
    }, 0);
  }, [orders]);

  return (
    <div className="space-y-6">
      <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div className="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
          <div className="flex items-start gap-4">
            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
              <ReceiptText className="h-5 w-5" />
            </div>

            <div>
              <h3 className="text-lg font-semibold tracking-tight text-slate-900">
                Sales Orders
              </h3>
              <p className="mt-1 text-sm text-slate-500">
                Review customer orders, channels, and order lifecycle state.
              </p>
            </div>
          </div>

          <div className="flex flex-col gap-3 md:flex-row">
            <div className="flex w-full min-w-[320px] items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
              <Search className="h-4 w-4 text-slate-400" />
              <input
                className="w-full border-0 bg-transparent text-sm outline-none placeholder:text-slate-400"
                placeholder="Search by order number"
                value={searchInput}
                onChange={(e) => setSearchInput(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === "Enter") {
                    setSearch(searchInput.trim());
                  }
                }}
              />
            </div>

            <button
              onClick={() => setSearch(searchInput.trim())}
              className="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800"
            >
              Search
            </button>

            <button className="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
              <SlidersHorizontal className="h-4 w-4" />
              Filters
            </button>
          </div>
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-3">
        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-slate-500">Total Visible</p>
              <p className="mt-3 text-3xl font-semibold tracking-tight text-slate-900">
                {orders.length}
              </p>
            </div>
            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
              <ShoppingCart className="h-5 w-5" />
            </div>
          </div>
        </div>

        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-slate-500">Visible Revenue</p>
              <p className="mt-3 text-3xl font-semibold tracking-tight text-slate-900">
                {formatMoney(visibleRevenue)}
              </p>
            </div>
            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
              <ReceiptText className="h-5 w-5" />
            </div>
          </div>
        </div>

        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-slate-500">Completed Orders</p>
              <p className="mt-3 text-3xl font-semibold tracking-tight text-slate-900">
                {orders.filter((order: any) => order.status === "completed").length}
              </p>
            </div>
            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
              <Store className="h-5 w-5" />
            </div>
          </div>
        </div>
      </div>

      <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
          <div>
            <h3 className="text-lg font-semibold tracking-tight text-slate-900">
              Orders
            </h3>
            <p className="mt-1 text-sm text-slate-500">
              Sales orders across retail, online, phone, and custom build flows.
            </p>
          </div>
        </div>

        {isLoading ? (
          <div className="p-6 text-sm text-slate-500">Loading orders...</div>
        ) : error ? (
          <div className="p-6 text-sm text-red-600">Failed to load orders.</div>
        ) : orders.length === 0 ? (
          <div className="p-10 text-center text-sm text-slate-500">
            No orders found.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full">
              <thead className="bg-slate-50">
                <tr className="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                  <th className="px-5 py-4">Order Number</th>
                  <th className="px-5 py-4">Customer</th>
                  <th className="px-5 py-4">Channel</th>
                  <th className="px-5 py-4">Status</th>
                  <th className="px-5 py-4">Type</th>
                  <th className="px-5 py-4">Total</th>
                </tr>
              </thead>

              <tbody className="divide-y divide-slate-100">
                {orders.map((order: any) => (
                  <tr key={order.id} className="transition hover:bg-slate-50">
                    <td className="px-5 py-4 text-sm font-semibold text-slate-900">
                      {order.order_number}
                    </td>

                    <td className="px-5 py-4">
                      <p className="text-sm font-medium text-slate-900">
                        {order.customer?.display_name ?? "Unknown Customer"}
                      </p>
                    </td>

                    <td className="px-5 py-4">
                      <span
                        className={[
                          "inline-flex rounded-full px-2.5 py-1 text-xs font-semibold",
                          channelTone(order.channel),
                        ].join(" ")}
                      >
                        {order.channel}
                      </span>
                    </td>

                    <td className="px-5 py-4">
                      <span
                        className={[
                          "inline-flex rounded-full px-2.5 py-1 text-xs font-semibold",
                          statusTone(order.status),
                        ].join(" ")}
                      >
                        {order.status}
                      </span>
                    </td>

                    <td className="px-5 py-4 text-sm text-slate-700">
                      {order.order_type}
                    </td>

                    <td className="px-5 py-4 text-sm font-semibold text-slate-900">
                      {formatMoney(order.total_amount)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}