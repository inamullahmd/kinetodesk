import { api } from "./client";

export type PurchaseOrderFilters = {
  search?: string;
  status?: string;
  supplier_id?: number;
  page?: number;
};

export async function getPurchaseOrders(filters: PurchaseOrderFilters = {}) {
  const { data } = await api.get("/purchase-orders", { params: filters });
  return data;
}