import { useQuery } from "@tanstack/react-query";
import { Package, Search, SlidersHorizontal } from "lucide-react";
import { useMemo, useState } from "react";
import { getProducts } from "../api/products";

function formatMoney(value: number | string | null | undefined) {
  const numericValue = Number(value ?? 0);
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
    maximumFractionDigits: 2,
  }).format(numericValue);
}

function stockTone(current: number, reserved: number) {
  const available = current - reserved;

  if (available <= 0) return "text-red-600 bg-red-50";
  if (available <= 10) return "text-amber-700 bg-amber-50";
  return "text-emerald-700 bg-emerald-50";
}

export function ProductsPage() {
  const [searchInput, setSearchInput] = useState("");
  const [search, setSearch] = useState("");

  const { data, isLoading, error } = useQuery({
    queryKey: ["products", search],
    queryFn: () => getProducts({ search, active_only: true }),
  });

  const products = useMemo(() => data?.data ?? [], [data]);

  return (
    <div className="space-y-6">
      <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div className="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
          <div className="flex items-start gap-4">
            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
              <Package className="h-5 w-5" />
            </div>

            <div>
              <h3 className="text-lg font-semibold tracking-tight text-slate-900">
                Product Catalog
              </h3>
              <p className="mt-1 text-sm text-slate-500">
                Search and review inventory-facing product records.
              </p>
            </div>
          </div>

          <div className="flex flex-col gap-3 md:flex-row">
            <div className="flex w-full min-w-[320px] items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
              <Search className="h-4 w-4 text-slate-400" />
              <input
                className="w-full border-0 bg-transparent text-sm outline-none placeholder:text-slate-400"
                placeholder="Search by SKU, name, or description"
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

      <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
          <div>
            <h3 className="text-lg font-semibold tracking-tight text-slate-900">
              Products
            </h3>
            <p className="mt-1 text-sm text-slate-500">
              {products.length} visible item{products.length === 1 ? "" : "s"}
            </p>
          </div>
        </div>

        {isLoading ? (
          <div className="p-6 text-sm text-slate-500">Loading products...</div>
        ) : error ? (
          <div className="p-6 text-sm text-red-600">Failed to load products.</div>
        ) : products.length === 0 ? (
          <div className="p-10 text-center text-sm text-slate-500">
            No products found.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full">
              <thead className="bg-slate-50">
                <tr className="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                  <th className="px-5 py-4">SKU</th>
                  <th className="px-5 py-4">Product</th>
                  <th className="px-5 py-4">Type</th>
                  <th className="px-5 py-4">Stock</th>
                  <th className="px-5 py-4">Reserved</th>
                  <th className="px-5 py-4">Price</th>
                </tr>
              </thead>

              <tbody className="divide-y divide-slate-100">
                {products.map((product: any) => {
                  const current = Number(product.current_stock ?? 0);
                  const reserved = Number(product.reserved_stock ?? 0);

                  return (
                    <tr key={product.id} className="transition hover:bg-slate-50">
                      <td className="px-5 py-4 text-sm font-medium text-slate-600">
                        {product.sku}
                      </td>

                      <td className="px-5 py-4">
                        <div>
                          <p className="text-sm font-semibold text-slate-900">
                            {product.name}
                          </p>
                          <p className="mt-1 text-xs text-slate-500">
                            {product.is_serialized ? "Serialized item" : "Standard item"}
                          </p>
                        </div>
                      </td>

                      <td className="px-5 py-4">
                        <span className="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                          {product.product_type}
                        </span>
                      </td>

                      <td className="px-5 py-4">
                        <span
                          className={[
                            "inline-flex rounded-full px-2.5 py-1 text-xs font-semibold",
                            stockTone(current, reserved),
                          ].join(" ")}
                        >
                          {current}
                        </span>
                      </td>

                      <td className="px-5 py-4 text-sm text-slate-700">
                        {reserved}
                      </td>

                      <td className="px-5 py-4 text-sm font-semibold text-slate-900">
                        {formatMoney(product.sell_price)}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}