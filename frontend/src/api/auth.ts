import { api } from "./client";

export type LoginPayload = {
  email: string;
  password: string;
};

export async function login(payload: LoginPayload) {
  const { data } = await api.post("/login", payload);
  return data;
}

export async function me() {
  const { data } = await api.get("/me");
  return data;
}

export async function logout() {
  const { data } = await api.post("/logout");
  return data;
}