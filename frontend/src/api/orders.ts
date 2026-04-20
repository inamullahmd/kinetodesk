import { api } from "./client";

export type OrderFilters = {
  search?: string;
  status?: string;
  channel?: string;
  page?: number;
};

export async function getOrders(filters: OrderFilters = {}) {
  const { data } = await api.get("/orders", { params: filters });
  return data;
}