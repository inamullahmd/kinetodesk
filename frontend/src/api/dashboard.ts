import { api } from "./client";

export async function getDashboardOverview() {
  const { data } = await api.get("/dashboard/overview");
  return data;
}