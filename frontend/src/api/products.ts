import { api } from "./client";

export type ProductFilters = {
  search?: string;
  active_only?: boolean;
  low_stock_only?: boolean;
  serialized_only?: boolean;
  product_type?: string;
  page?: number;
};

export async function getProducts(filters: ProductFilters = {}) {
  const { data } = await api.get("/products", { params: filters });
  return data;
}